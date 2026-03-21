<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef0f3;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
@php
    $primaryColor = $settings->primary_color ?? '#2563eb';
    $siteTitle    = $settings->site_title ?? ($tenant->name ?? 'BusyRealtor');
    $faviconUrl   = ($settings->favicon_preset ?? null) ? url('/' . $tenant->slug . '/favicon.svg?color=ffffff') : null;
    $faviconBg    = 'rgba(255,255,255,0.2)';
    $contactEmail = $settings->contact_email ?? null;
    $address      = $settings->contact_address ?? null;

    if (!function_exists('renderEmailBody')) {
    function renderEmailBody(string $raw): string {
        $lines  = explode("\n", $raw);
        $output = '';
        $kvRows = [];

        $flushKv = function () use (&$kvRows, &$output) {
            if (empty($kvRows)) return;
            $output .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;border-collapse:collapse;">';
            foreach ($kvRows as [$k, $v]) {
                $output .= '<tr>'
                    . '<td style="padding:7px 12px 7px 0;color:#6b7280;font-size:13px;font-weight:600;white-space:nowrap;vertical-align:top;width:1%;border-bottom:1px solid #f3f4f6;">' . e($k) . '</td>'
                    . '<td style="padding:7px 0 7px 12px;color:#111827;font-size:14px;vertical-align:top;border-bottom:1px solid #f3f4f6;">' . formatBodyValue($v) . '</td>'
                    . '</tr>';
            }
            $output .= '</table>';
            $kvRows = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^[─\-=_]{4,}$/', $trimmed)) {
                $flushKv();
                $output .= '<hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;">';
                continue;
            }

            if (preg_match('/^([A-Za-z][A-Za-z0-9 _\-]{1,24}):\s+(.+)$/', $trimmed, $m)) {
                $kvRows[] = [$m[1], $m[2]];
                continue;
            }

            $flushKv();

            if ($trimmed === '') {
                $output .= '<div style="height:12px;"></div>';
            } else {
                $output .= '<p style="margin:0 0 10px;color:#1f2937;font-size:15px;line-height:1.7;">' . formatBodyValue($trimmed) . '</p>';
            }
        }

        $flushKv();
        return $output;
    }

    function formatBodyValue(string $text): string {
        $text = e($text);
        $text = preg_replace(
            '/https?:\/\/[^\s<>"]+/',
            '<a href="$0" style="color:#2563eb;text-decoration:underline;">$0</a>',
            $text
        );
        return $text;
    }
    } // end function_exists
@endphp

<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">{{ $subject }} &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0f3;">
  <tr>
    <td align="center" style="padding:32px 16px 40px;">

      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        {{-- Header --}}
        <tr>
          <td align="center" style="background-color:{{ $primaryColor }};padding:28px 40px 24px;border-radius:8px 8px 0 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                <tr>
                    @if($faviconUrl)
                    <td style="vertical-align:middle;padding-right:10px;width:40px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="36" style="width:36px;">
                            <tr>
                                <td align="center" width="36" height="36" style="width:36px;height:36px;border-radius:8px;border:2px solid rgba(255,255,255,0.5);background:rgba(255,255,255,0.2);">
                                    <img src="{{ $faviconUrl }}" alt="" width="22" height="22" style="display:block;margin:0 auto;border:0;" />
                                </td>
                            </tr>
                        </table>
                    </td>
                    @endif
                    <td style="vertical-align:middle;">
                        <span style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.3px;line-height:1.2;">{{ $siteTitle }}</span>
                    </td>
                </tr>
            </table>
          </td>
        </tr>

        {{-- Accent stripe --}}
        <tr>
          <td style="background-color:{{ $primaryColor }};height:3px;opacity:0.3;font-size:0;line-height:0;">&nbsp;</td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background-color:#ffffff;padding:36px 44px 32px;">
            <h2 style="margin:0 0 22px;font-size:19px;font-weight:700;color:#111827;line-height:1.3;border-bottom:2px solid {{ $primaryColor }};padding-bottom:14px;">{{ $subject }}</h2>
            <div style="color:#374151;font-size:15px;line-height:1.75;">
              {!! renderEmailBody($body) !!}
            </div>
          </td>
        </tr>

        {{-- Agent card --}}
        @if(!empty($agent['name']))
        <tr>
          <td style="background-color:#ffffff;padding:0 44px 28px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e5e7eb;padding-top:20px;">
              <tr>
                <td colspan="2" style="padding-bottom:12px;text-align:center;">
                  <p style="margin:0;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Your Agent</p>
                </td>
              </tr>
              <tr>
                @if(!empty($agent['photo']))
                <td width="96" style="vertical-align:middle;padding-right:16px;">
                  <img src="{{ $agent['photo'] }}" alt="{{ $agent['name'] }}" width="80" height="80" style="display:block;width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid {{ $settings->primary_color ?? '#2563eb' }};" />
                </td>
                @endif
                <td style="vertical-align:middle;">
                  <p style="margin:0;font-size:15px;font-weight:700;color:#111827;">{{ $agent['name'] }}</p>
                  @if(!empty($agent['title']))
                  <p style="margin:3px 0 0;font-size:13px;color:{{ $settings->primary_color ?? '#2563eb' }};">{{ $agent['title'] }}</p>
                  @endif
                  @if(!empty($agent['email']))
                  <p style="margin:5px 0 0;font-size:12px;color:#6b7280;"><a href="mailto:{{ $agent['email'] }}" style="color:#6b7280;text-decoration:none;">{{ $agent['email'] }}</a></p>
                  @endif
                  @if(!empty($agent['phone']))
                  <p style="margin:2px 0 0;font-size:12px;color:#6b7280;"><a href="tel:{{ $agent['phone'] }}" style="color:#6b7280;text-decoration:none;">{{ $agent['phone'] }}</a></p>
                  @endif
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif

        {{-- Footer --}}
        <tr>
          <td style="background-color:#f8f9fb;border-top:1px solid #e5e7eb;border-radius:0 0 8px 8px;padding:20px 44px;">
            <p style="margin:0 0 4px;font-size:12px;color:#374151;font-weight:600;">{{ $siteTitle }}</p>
            @if($contactEmail)
            <p style="margin:0 0 2px;font-size:12px;color:#6b7280;">
              <a href="mailto:{{ $contactEmail }}" style="color:#6b7280;text-decoration:none;">{{ $contactEmail }}</a>
            </p>
            @endif
            @if($address)
            <p style="margin:0;font-size:12px;color:#6b7280;">{{ $address }}</p>
            @endif
            <p style="margin:14px 0 0;padding-top:12px;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;">
              Powered by <a href="https://busyrealtor.com" style="color:#9ca3af;text-decoration:none;font-weight:600;">BusyRealtor</a>
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
