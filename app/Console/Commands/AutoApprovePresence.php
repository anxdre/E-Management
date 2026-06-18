<?php

namespace App\Console\Commands;

use App\AutoApproveHelper;
use App\Models\CompanyProfile;
use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\PresenceManagement\PresenceLocation;

class AutoApprovePresence
{
    use AutoApproveHelper;

    public function __invoke(): void
    {
        $settings = CompanyProfile::first();
        if (!$settings?->auto_approve || $settings->auto_approve_mode !== 'cron') {
            return;
        }

        $nowHour = now()->format('H:i');
        $batchHour = $settings->auto_approve_batch_hour
            ? substr($settings->auto_approve_batch_hour, 0, 5)
            : null;
        if (!$batchHour || $nowHour !== $batchHour) {
            return;
        }

        PresenceEmployee::where('status_by_admin', 'pending')
            ->whereNotNull('time_out')
            ->chunk(100, function ($presences) use ($settings) {
                foreach ($presences as $presence) {
                    $location = PresenceLocation::find($presence->mst_presence_location_id);
                    if (!$location) {
                        continue;
                    }

                    if ($this->canAutoApprove($presence, $location, $settings)) {
                        $presence->update(['status_by_admin' => 'approved']);
                    }
                }
            });
    }
}
