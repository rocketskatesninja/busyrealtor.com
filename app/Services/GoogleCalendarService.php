<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Integration;
use App\Models\SystemSetting;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventAttendee;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private Integration $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    private function getClient(): GoogleClient
    {
        $sys = SystemSetting::get();

        $client = new GoogleClient();
        $client->setClientId($sys->google_client_id);
        $client->setClientSecret($sys->google_client_secret);
        $client->setAccessType('offline');

        $config = $this->integration->config;
        $client->setAccessToken([
            'access_token'  => $config['access_token'],
            'refresh_token' => $config['refresh_token'] ?? null,
            'expires_in'    => $config['expires_in'] ?? 3600,
            'created'       => $config['created'] ?? time(),
        ]);

        // Refresh if expired
        if ($client->isAccessTokenExpired() && !empty($config['refresh_token'])) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($config['refresh_token']);
            if (!isset($newToken['error'])) {
                $config['access_token'] = $newToken['access_token'];
                $config['expires_in']   = $newToken['expires_in'] ?? 3600;
                $config['created']      = $newToken['created'] ?? time();
                if (!empty($newToken['refresh_token'])) {
                    $config['refresh_token'] = $newToken['refresh_token'];
                }
                $this->integration->update(['config' => $config]);
            }
        }

        return $client;
    }

    public function createEvent(Appointment $appointment): ?string
    {
        try {
            $client  = $this->getClient();
            $service = new Calendar($client);

            $appointment->loadMissing(['property', 'staffMember']);

            // Build summary
            $summary = ucfirst($appointment->appointment_type ?? 'Showing') . ' - ' . $appointment->visitor_name;

            // Build location from property
            $location = '';
            if ($appointment->property) {
                $p = $appointment->property;
                $parts = array_filter([$p->address_street, $p->address_city, $p->address_state, $p->address_zip]);
                $location = implode(', ', $parts);
            }

            // Build description
            $desc = "Appointment booked via BusyRealtor\n";
            $desc .= "Type: " . ucfirst($appointment->appointment_type ?? 'showing') . "\n";
            $desc .= "Visitor: {$appointment->visitor_name}\n";
            $desc .= "Email: {$appointment->visitor_email}\n";
            if ($appointment->visitor_phone) {
                $desc .= "Phone: {$appointment->visitor_phone}\n";
            }
            if ($appointment->property) {
                $desc .= "Property: {$appointment->property->title}\n";
            }
            if ($appointment->staffMember) {
                $desc .= "Agent: {$appointment->staffMember->name}\n";
            }
            if ($appointment->notes) {
                $desc .= "\nNotes: {$appointment->notes}\n";
            }

            // Build start/end times
            $date = $appointment->appointment_date->format('Y-m-d');
            $time = $appointment->appointment_time ?? '09:00:00';
            $duration = $appointment->duration_minutes ?? 30;

            $startDt = \Carbon\Carbon::parse("{$date} {$time}", 'America/New_York');
            $endDt   = $startDt->copy()->addMinutes($duration);

            $start = new EventDateTime();
            $start->setDateTime($startDt->toRfc3339String());
            $start->setTimeZone('America/New_York');

            $end = new EventDateTime();
            $end->setDateTime($endDt->toRfc3339String());
            $end->setTimeZone('America/New_York');

            $event = new Event();
            $event->setSummary($summary);
            $event->setLocation($location);
            $event->setDescription($desc);
            $event->setStart($start);
            $event->setEnd($end);

            // Add visitor as attendee so they get a calendar invite
            if ($appointment->visitor_email) {
                $attendee = new EventAttendee();
                $attendee->setEmail($appointment->visitor_email);
                $event->setAttendees([$attendee]);
            }

            $created = $service->events->insert('primary', $event, ['sendUpdates' => 'all']);

            return $created->getId();
        } catch (\Throwable $e) {
            Log::error('Google Calendar createEvent failed', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function deleteEvent(string $eventId): void
    {
        try {
            $client  = $this->getClient();
            $service = new Calendar($client);
            $service->events->delete('primary', $eventId, ['sendUpdates' => 'all']);
        } catch (\Throwable $e) {
            Log::error('Google Calendar deleteEvent failed', [
                'event_id' => $eventId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
