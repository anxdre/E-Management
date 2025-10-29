<?php

namespace App\Http\Controllers\MobileApi;

use App\DateHelper;
use App\Http\Resources\JsonBody;
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

#[Prefix('Presence'), Name('api-presence'), Middleware('auth:sanctum')]
class PrensenceApiController
{
    use DateHelper, PositionHelper;

    #[Get('/{user}', '.all', ['auth:sanctum'])]
    public function getAllHistory(request $request, User $user)
    {
        $request->validate([
            'presence_location_id' => 'nullable|exists:presence_locations,id',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $data = PresenceEmployee::query()
            ->where('user_id', $user->id)
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
                return $query->where('presence_location_id', $request->get('location_id'));
            })
            ->orderBy('time_in', $request->orderBy ?? 'desc')
            ->paginate(10, page: $request->currentPage ?? 1)
            ->withQueryString();

        return new JsonBody($data);
    }


    #[Get('/{user}/latest', '.all', ['auth:sanctum'])]
    public function getLastestHistory(request $request, User $user)
    {
        $request->validate(['presence_location_id' => 'nullable|exists:presence_locations,id']);

        $data = PresenceEmployee::query()
            ->where('user_id', $user->id)
            ->when(!empty($request->location_id), function ($query) use ($request) {
                return $query->where('presence_location_id', $request->get('location_id'));
            })
            ->latest()
            ->first();

        return new JsonBody($data);
    }


    #[Post('{user}/create/json', '.json.create', ['scope-company'])]
    public function create(Request $request, User $user)
    {
        $request->validate([
            'code' => 'nullable|numeric',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_id' => 'required|exists:presence_locations,id',
            'note' => 'nullable',
            'time' => 'nullable',
            'status' => ['required', Rule::in(['in', 'out'])],
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx',]);

        $verificationCode = PresenceVerification::query()
            ->select(['verification_hash', 'id'])
            ->where('presence_location_id', $request->get('location_id'))
            ->first();

        if ($verificationCode == null) {
            return new JsonBody([], 'Verification code not been setup, Contact your administrator', 404);
        }

        if (Auth::user()->isEmployee() && $request->get('code') !== $verificationCode->verification_hash) {
            return new JsonBody([], 'Wrong verification code', 404);
        }

        $presenceLocation = PresenceLocation::query()->find($request->get('location_id'));
        $historyAttendance = PresenceEmployee::query()
            ->where('user_id', $user->id)
            ->where('presence_location_id', $presenceLocation->id)
            ->latest('time')
            ->first();

        $employeePresence = new PresenceEmployee([
            'user_id' => $user->id,
            'presence_location_id' => $request->get('location_id'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'note' => $request->get('note'),
            'status_by_admin' => Auth::user()->type == 'company' ? 'approved' : 'pending',
        ]);

        if ($request->status == 'in' && Auth::user()->isEmployee()) {
            if ($presenceLocation->start_hour) {
                $startTimeToday = Carbon::createFromFormat('H:i:s', $presenceLocation->start_hour)
                    ->setDateFrom(Carbon::now());
                if (Carbon::now()->lt($startTimeToday)) {
                    return new JsonBody([], 'Belum waktunya absen masuk, tunggu sampai jam ' . $startTimeToday->format('H:i:s'), 422);
                }
            }
            if ($historyAttendance && $historyAttendance->time_in || $historyAttendance->presence_location_id != $presenceLocation->id) {
                return new JsonBody([], 'Mohon check-out di lokasi anda check-in terlebih dahulu', 403);
            }
            $employeePresence->time_in = Carbon::now();
        }

        if ($request->status == 'out' && Auth::user()->isEmployee()) {
            if ($historyAttendance && $historyAttendance->time_out || $historyAttendance->presence_location_id != $presenceLocation->id) {
                return new JsonBody([], 'Data check-in tidak terdeteksi', 403);
            }

            $isLimit = false;
            $isEarlier = false;
            if ($presenceLocation->max_hour) {
                $isLimit = Carbon::parse($employeePresence->time)->diffInHours($historyAttendance->time, true) > $presenceLocation->max_hour;
            }

            if ($presenceLocation->min_hour) {
                $isEarlier = Carbon::parse($employeePresence->time)->diffInHours($historyAttendance->time) < $presenceLocation->min_hour;
            }

            if ($isLimit) {
                $employeePresence->extended_time = $this->diffTimeFormatted($presenceLocation->max_hour, $historyAttendance->time);
                $employeePresence->note = "*late check out !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = 'pending';
            }

            if ($isEarlier) {
                $employeePresence->extended_time = $this->diffTimeMinusFormatted($presenceLocation->max_hour, $historyAttendance->time);
                $employeePresence->note = "*Earlier check out !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = 'pending';
            }
        }

        if (Auth::user()->isEmployee()) {
            $distance = $this->haversineDistance(
                $request->get('latitude'),
                $request->get('longitude'),
                $presenceLocation->latitude,
                $presenceLocation->longitude
            );

            if ($distance > $presenceLocation->tolerance) {
                $employeePresence->note = "*Position Out Of Radius !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = Auth::user()->isCompany ? 'approved' : 'pending';
            }
        }

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $timestamp = now()->format('YmdHis');
            $filename = "$timestamp." . $attachment->getClientOriginalExtension();

            // Simpan ke public storage path
            $path = "attendance/attachment/{$request->get('user_id')}";
            Storage::putFileAs("public/$path", $attachment, $filename);

            // Simpan path ke DB (tanpa "public/")
            $employeePresence->attachment = "$path/$filename";
        }

        if (!$employeePresence->save()) {
            return new JsonBody([], 'Something went error on server, call administrator or HRD', 500);
        }

        return new JsonBody($employeePresence, 'Success');
    }
}
