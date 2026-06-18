<?php

namespace App;

use App\Models\CompanyProfile;
use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\PresenceManagement\PresenceLocation;
use Illuminate\Support\Carbon;

trait AutoApproveHelper
{
    function canAutoApprove(PresenceEmployee $presence, PresenceLocation $location, CompanyProfile $settings): bool
    {
        // 1. Radius violation — always block
        if (str_contains($presence->note ?? '', 'Out Of Radius')) {
            return false;
        }

        // 2. Late check-in — only if location has end_hour
        if ($location->end_hour && str_contains($presence->note ?? '', 'Late check in')) {
            return false;
        }

        // 3. Early checkout — only if location has min_hour
        if ($location->min_hour && str_contains($presence->note ?? '', 'Earlier check out')) {
            return false;
        }

        // 4. Late checkout — only if location has max_hour
        if ($location->max_hour && str_contains($presence->note ?? '', 'late check out')) {
            return false;
        }

        // 5. Duplicate GPS coordinates (optional toggle)
        if ($settings->auto_approve_duplicate_coords && $presence->latitude && $presence->longitude) {
            $query = PresenceEmployee::where('mst_user_id', $presence->mst_user_id)
                ->where('latitude', $presence->latitude)
                ->where('longitude', $presence->longitude)
                ->whereDate('time_in', '>=', Carbon::now()->subDays(7));

            // Exclude current record if it has an ID (checkout update)
            if ($presence->id) {
                $query->where('id', '!=', $presence->id);
            }

            if ($query->exists()) {
                return false;
            }
        }

        // 6. Minimum work duration (optional toggle, only if both time_in and time_out available)
        if ($settings->auto_approve_min_duration > 0 && $presence->time_in && $presence->time_out) {
            $duration = Carbon::parse($presence->time_in)->diffInMinutes(Carbon::parse($presence->time_out));
            if ($duration < $settings->auto_approve_min_duration) {
                return false;
            }
        }

        return true;
    }
}
