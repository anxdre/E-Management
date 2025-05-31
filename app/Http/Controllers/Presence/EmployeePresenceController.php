<?php

namespace App\Http\Controllers\Presence;

use App\DateHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\PresenceManagement\PresenceLocation;
use App\Models\PresenceManagement\PresenceVerification;
use App\Models\UserManagement\User;
use App\PositionHelper;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use function PHPUnit\Framework\isEmpty;

#[Prefix('Employee'), Name('employee-presence'), Middleware('auth')]
class EmployeePresenceController extends Controller
{
    use DateHelper,PositionHelper;

    #[Get('/{user}/Presence-History', '.index', ['scope-company'])]
    public function index(Request $request)
    {
        return Inertia::render('Employee/EmployeePresence/PresenceHistory');
    }

    #[Get('/{user}/json', '.json.all', ['scope-company'])]
    public function getAllHistory(request $request,User $user)
    {
        $request->validate(['presence_location_id' => 'nullable|exists:presence_locations,id',
            'time.*' => 'nullable|string',]);

        $data = PresenceEmployee::query()
            ->where('user_id', $user->id)
            ->when($request->has('presence_location_id'), function ($query) use ($request) {
                $query->where('presence_location_id', $request->input('presence_location_id'));
            })
            ->when($request->has('time'), function ($query) use ($request) {
                $query->whereBetween('time', [$request->input('time')['start'], $request->input('time')['end']]);
            })
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

    #[Post('{user}/create/json', 'json.create', ['scope-company'])]
    public function create(Request $request,User $user)
    {
        $request->validate([
            'code' => 'required|numeric',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_id' => 'required|exists:presence_locations,id',
            'note' => 'nullable|string',
            'time' => 'string',
            'status' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx',]);

        $verificationCode = PresenceVerification::query()
            ->select('verification_hash')
            ->where('presence_location_id', $request->get('location_id'))
            ->first();

        if ($verificationCode == null){
            return new JsonBody([], 'Verification code not been setup, Contact your administrator', 404);
        }

        if ($user->isEmployee() && $request->get('code') !== $verificationCode->verification_hash) {
            return new JsonBody([], 'Wrong verification code', 404);
        }

        $presenceLocation = PresenceLocation::query()->find($request->get('location_id'));
        $historyAttendance = PresenceEmployee::query()
            ->where('user_id', $user->id)
            ->where('presence_location_id', $presenceLocation->id)
            ->latest('time')
            ->first();

        // Cek start_hour
        if (Auth::user()->isEmployee() && $presenceLocation->start_hour) {
            $startTimeToday = Carbon::createFromFormat('H:i:s', $presenceLocation->start_hour)
                ->setDateFrom(Carbon::now());

            $isTryingToCheckIn = !$historyAttendance || $historyAttendance->status === 'out';

            if ($isTryingToCheckIn && Carbon::now()->lt($startTimeToday)) {
                return new JsonBody([], 'Belum waktunya absen masuk, tunggu sampai jam ' . $startTimeToday->format('H:i:s'), 422);
            }
        }

        $employeePresence = new PresenceEmployee([
            'user_id' => $user->id,
            'presence_location_id' => $request->get('location_id'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'note' => $request->get('note'),
            'status' =>  Auth::user()->type == 'company' ? $request->get('status') : 'out',
            'time' => !is_null($request->get('time')) && Auth::user()->type == 'company' ? Carbon::parse($request->get('time')) : Carbon::now(),
            'status_by_admin' => Auth::user()->type == 'company' ? 'approved' : 'pending',
        ]);

        if ((Auth::user()->isEmployee() && $historyAttendance->status == 'in') || (Auth::user()->isCompany() && $employeePresence->status == 'out' && $historyAttendance->status == 'in')) {
            $employeePresence->status = 'out';
            //check max hour
            $isLimit = false;
            $isEarlier = false;
            if ($presenceLocation->max_hour) {
                $isLimit = Carbon::parse($employeePresence->time)->diffInHours($historyAttendance->time, true) > $presenceLocation->max_hour;
            }

            if ($presenceLocation->min_hour){
                $isEarlier = Carbon::parse($employeePresence->time)->diffInHours($historyAttendance->time) < $presenceLocation->min_hour;
            }

            if ($isLimit) {
                $employeePresence->extended_time = $this->diffTimeFormatted($presenceLocation->max_hour, $historyAttendance->time);
                $employeePresence->note = "*late check out !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = Auth::user()->isCompany ? 'approved' : 'pending';
            }

            if ($isEarlier) {
                $employeePresence->extended_time = $this->diffTimeMinusFormatted($presenceLocation->max_hour, $historyAttendance->time);
                $employeePresence->note = "*Earlier check out !, need admin verification \n\n $employeePresence->note";
                $employeePresence->status_by_admin = Auth::user()->isCompany ? 'approved' : 'pending';
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
            Storage::putFileAs("/attendance/attachment/{$request->get('user_id')}/", $attachment, $filename);
            $employeePresence->attachment = "/attendance/attachment/{$request->get('user_id')}/$filename";
        }

        if (!$employeePresence->save()) {
            return new JsonBody([], 'Something went error on server, call administrator or HRD', 500);
        }

        return new JsonBody($employeePresence, 'Success');
    }
}
