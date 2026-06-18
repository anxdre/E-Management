<?php

namespace App\Http\Controllers\MobileApi;

use App\AutoApproveHelper;
use App\DateHelper;
use App\Exports\PresenceHistoryExport;
use App\Http\Resources\JsonBody;
use App\Models\CompanyProfile;
use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\PresenceManagement\PresenceLocation;
use App\Models\PresenceManagement\PresenceVerification;
use App\Models\UserManagement\User;
use App\PositionHelper;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

#[Prefix('Presence'), Name('api-presence'), Middleware('auth:sanctum')]
class PrensenceApiController
{
    use DateHelper, PositionHelper, AutoApproveHelper;

    #[Get('/{user}', '.all', ['auth:sanctum'])]
    public function getAllHistory(request $request, User $user)
    {
        $request->validate([
            'mst_presence_location_id' => 'nullable|exists:mst_presence_locations,id',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $data = PresenceEmployee::query()
            ->where('mst_user_id', $user->id)
            ->with(['presenceLocation'])
            ->when(!empty($request->status), function ($query) use ($request) {
                return $query->where('status_by_admin',  $request->get('status'));
            })
            ->when(!empty($request->date_start) && !empty($request->date_end), function ($query) use ($request) {
                return $query->whereBetween('created_at', [
                    $request->get('date_start'),
                    $request->get('date_end'),
                ]);
            })
            ->when(!empty($request->location_id), function ($query) use ($request) {
                return $query->where('mst_presence_location_id', $request->get('location_id'));
            })
            ->orderBy('time_in', $request->orderBy ?? 'desc')
            ->paginate(10, page: $request->currentPage ?? 1)
            ->withQueryString();

        return new JsonBody($data);
    }


    #[Get('/{user}/latest', '.all', ['auth:sanctum'])]
    public function getLastestHistory(request $request, User $user)
    {
        $request->validate(['mst_presence_location_id' => 'nullable|exists:mst_presence_locations,id']);

        $data = PresenceEmployee::query()
            ->where('mst_user_id', $user->id)
            ->where('time_out', null)
            ->when(!empty($request->location_id), function ($query) use ($request) {
                return $query->where('mst_presence_location_id', $request->get('location_id'));
            })
            ->with(['presenceLocation'])
            ->latest()
            ->first();

        if (!$data) {
            return new JsonBody(null, 'No presence history found', 200);
        }

        $diff = Carbon::parse($data->time_in)->diff(Carbon::now());
        $data->work_hour = sprintf('%d jam %d menit', $diff->h, $diff->i);

        return new JsonBody($data, 'Success');
    }


    #[Post('api/create/', '.api.create', ['auth:sanctum'])]
    public function create(Request $request)
    {
        $request->validate([
            'mst_user_id' => 'required|exists:mst_users,id',
            'code' => 'nullable|numeric',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_id' => 'required|exists:mst_presence_locations,id',
            'note' => 'nullable',
            'time' => 'nullable',
            'status' => ['required', Rule::in(['in', 'out'])],
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx',
        ]);

        $user = User::query()->findOrFail($request->get('mst_user_id'));

        $verificationCode = PresenceVerification::query()
            ->select(['verification_hash', 'id'])
            ->where('mst_presence_location_id', $request->get('location_id'))
            ->first();

        if ($verificationCode == null) {
            return new JsonBody([], 'Verification code not been setup, Contact your administrator', 404);
        }

        if ($user->isEmployee() && $request->get('code') != $verificationCode->verification_hash) {
            return new JsonBody([], 'Wrong verification code', 403);
        }

        $presenceLocation = PresenceLocation::query()->find($request->get('location_id'));
        $historyAttendance = PresenceEmployee::query()
            ->where('mst_user_id', $user->id)
            ->where('mst_presence_location_id', $presenceLocation->id)
            ->latest('created_at')
            ->first();

        $employeePresence = new PresenceEmployee([
            'mst_user_id' => $user->id,
            'mst_presence_location_id' => $request->get('location_id'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'note' => $request->get('note'),
            'trx_presence_verification_id' => $verificationCode->id,
            'status_by_admin' => $user->type == 'company' ? 'approved' : 'pending',
        ]);

        if ($request->status == 'in' && $user->isEmployee()) {
            if ($presenceLocation->start_hour) {
                $startTimeToday = Carbon::createFromFormat('H:i:s', $presenceLocation->start_hour)
                    ->setDateFrom(Carbon::now());
                if (Carbon::now()->lessThan($startTimeToday)) {
                    return new JsonBody([], 'Belum waktunya absen masuk, tunggu sampai jam ' . $startTimeToday->format('H:i:s'), 422);
                }
            }
            if ($historyAttendance && ($historyAttendance->time_in && !$historyAttendance->time_out) || $historyAttendance->mst_presence_location_id != $presenceLocation->id) {
                return new JsonBody([], 'Mohon check-out di lokasi anda check-in terlebih dahulu', 403);
            }
            if ($presenceLocation->end_hour){
                $endTimeToday = Carbon::createFromFormat('H:i:s', $presenceLocation->end_hour)
                    ->setDateFrom(Carbon::now());
                if (Carbon::now()->greaterThan($endTimeToday)) {
                    $employeePresence->note .= "\n\n*Late check in !, need admin verification";
                }
            }
            $employeePresence->time_in = Carbon::now();
        }

        if ($request->status == 'out' && $user->isEmployee()) {
            if ($historyAttendance && ($historyAttendance->time_in && $historyAttendance->time_out) || $historyAttendance->mst_presence_location_id != $presenceLocation->id) {
                return new JsonBody([], 'Data check-in tidak terdeteksi', 403);
            }

            $employeePresence->time_out = Carbon::now();
            $employeePresence->id = $historyAttendance->id;
            $isLimit = false;
            $isEarlier = false;
            if ($presenceLocation->max_hour) {
                $isLimit = $employeePresence->time_out->diffInHours($employeePresence->time_out, true) > $presenceLocation->max_hour;
            }

            if ($presenceLocation->min_hour) {
                $isEarlier = $employeePresence->time_out->diffInHours($employeePresence->time_out) < $presenceLocation->min_hour;
            }

            if ($isLimit ) {
                $employeePresence->extended_time = $this->diffTimeFormatted(Carbon::parse($presenceLocation->max_hour), $employeePresence->time_out);
                $employeePresence->note = "*late check out !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = 'pending';
            }

            if ($isEarlier) {
                $employeePresence->extended_time = $this->diffTimeMinusFormatted(Carbon::parse($presenceLocation->max_hour), $employeePresence->time_out);
                $employeePresence->note = "*Earlier check out !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = 'pending';
            }
        }

        if ($user->isEmployee()) {
            $distance = $this->haversineDistance(
                $request->get('latitude'),
                $request->get('longitude'),
                $presenceLocation->latitude,
                $presenceLocation->longitude
            );

            if ($distance > $presenceLocation->tolerance) {
                $employeePresence->note = "*Position Out Of Radius !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = 'pending';
            }
        }

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $timestamp = now()->format('YmdHis');
            $filename = "$timestamp." . $attachment->getClientOriginalExtension();

            // Simpan ke public storage path
            $path = "attendance/attachment/{$request->get('mst_user_id')}";
            Storage::putFileAs("public/$path", $attachment, $filename);

            // Simpan path ke DB (tanpa "public/")
            $employeePresence->attachment = "$path/$filename";
        }

        // Auto-approve: realtime mode — upgrade to approved if all anomaly checks pass
        if ($user->isEmployee()) {
            $settings = CompanyProfile::first();
            if ($settings?->auto_approve && $settings->auto_approve_mode === 'realtime') {
                // For checkout, ensure time_in from history is available for min duration check
                if ($request->status == 'out' && $historyAttendance && !$employeePresence->time_in) {
                    $employeePresence->time_in = $historyAttendance->time_in;
                }
                if ($this->canAutoApprove($employeePresence, $presenceLocation, $settings)) {
                    $employeePresence->status_by_admin = 'approved';
                }
            }
        }

        if ($request->status == 'in'){
            $employeePresence->save();
        }

        if ($request->status == 'out'){
            $historyAttendance->update($employeePresence->toArray());
        }

        return new JsonBody($employeePresence, 'Success');
    }

    #[Get('api/export/excel', '.api.export.excel', ['auth:sanctum'])]
    public function exportExcel(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'status' => $request->status,
            'location_id' => $request->location_id,
            'date_start' => $request->date_start,
            'date_end' => $request->date_end,
            'order_by' => $request->orderBy ?? 'desc',
        ];

        $fileName = 'presence-history-' . $user->id . '-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new PresenceHistoryExport($user->id, $filters), $fileName);
    }
}
