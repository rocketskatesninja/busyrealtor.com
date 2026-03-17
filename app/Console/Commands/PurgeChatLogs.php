<?php

namespace App\Console\Commands;

use App\Models\ChatLog;
use App\Models\SiteSettings;
use App\Models\Tenant;
use Illuminate\Console\Command;

class PurgeChatLogs extends Command
{
    protected $signature = 'app:purge-chat-logs';
    protected $description = 'Delete expired chatbot conversation logs based on each tenant\'s chatbot_expiration setting';

    public function handle()
    {
        $tenants = Tenant::where('is_active', true)->get();
        $total = 0;

        foreach ($tenants as $tenant) {
            $settings = SiteSettings::where('tenant_id', $tenant->id)->first();
            $hours = (int) ($settings->chatbot_expiration ?? 24);
            if ($hours < 1) $hours = 24;

            $cutoff = now()->subHours($hours);
            $deleted = ChatLog::where('tenant_id', $tenant->id)
                ->where('session_id', 'not like', 'admin_%')
                ->where('created_at', '<', $cutoff)
                ->delete();
            $total += $deleted;
        }

        $this->info("Purged {$total} expired chat log entries.");
    }
}
