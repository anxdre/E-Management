<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Helper\AuthCheck;
use App\Http\Resources\JsonBody;
use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\PresenceManagement\PresenceLocation;
use App\Models\PresenceManagement\PresenceVerification;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

#[Prefix('Employee/Attendance'), Name('employee-attendance'), Middleware('auth')]
class EmployeeAttendanceController extends Controller
{
    use AuthCheck;

    #[Get('/{id}', '.index')]
    public function index($userId)
    {
        $granted = false;
        if ($this->isAdminOfCompany($userId)) {
            $granted = true;
        }
        if ($this->isEmployeeData($userId)) {
            $granted = true;
        }
        if (!$granted) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('General/Attendance/AttendanceHistory');
    }

    #[Get('/all/json', '.json.all')]
    public function getAllHistory(request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id',
            'presence_location_id' => 'nullable|exists:presence_locations,id',
            'time.*' => 'nullable|string',]);

        $data = PresenceEmployee::query()
            ->where('user_id', $request->input('user_id'))
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

    #[Post('/create', '.create')]
    public function createPage(Request $request)
    {
        $granted = false;
        $isAdmin = false;
        $request->validate(['user_id' => 'required|exists:users,id',
            'code' => 'required|number',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_id' => 'required|exists:presence_locations,id',
            'note' => 'nullable|string',
            'time' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx',]);

        if ($this->isAdminOfCompany($request->get('user_id'))) {
            $granted = true;
            $isAdmin = true;
        }
        if ($this->isEmployeeData($request->get('user_id'))) {
            $granted = true;
        }
        if (!$granted) {
            abort(403, 'Unauthorized action.');
        }

        $verificationCode = PresenceVerification::query()
            ->select('verification_hash')
            ->where('presence_location_id', $request->get('location_id'))
            ->first();

        if (!$isAdmin && $request->get('code') !== $verificationCode->verification_hash) {
            return new JsonBody([], 'Wrong verification code', 404);
        }

        $historyAttendance = PresenceEmployee::query()
            ->where('user_id', $request->get('user_id'))
            ->where('presence_location_id', $request->get('location_id'))
            ->first();

        $employeePresence = new PresenceEmployee([
            'user_id' => $request->get('user_id'),
            'presence_location_id' => $request->get('location_id'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'note' => $request->get('note'),
            'time' => !is_null($request->get('time')) && Auth::user()->type == 'company' ? Carbon::parse($request->get('time')) : Carbon::now(),
            'status_by_admin' => Auth::user()->type == 'company' ? 'approved' : 'pending'
        ]);

        if (!$historyAttendance) {
            $employeePresence->status = 'in';
        }

        if ($historyAttendance->status == 'in') {
            //check max hour
            $presenceLocation = PresenceLocation::query()->find($request->get('location_id'));
            $isLimit = Carbon::parse($employeePresence->time)->diffInHours($historyAttendance->time, true) > $presenceLocation->max_hour;

            if ($isLimit) {
                PresenceEmployee::query()->create([
                    'user_id' => $request->get('user_id'),
                    'presence_location_id' => $request->get('location_id'),
                    'latitude' => $request->get('latitude'),
                    'longitude' => $request->get('longitude'),
                    'note' => "Generated late check out, need admin verification",
                    'time' => $employeePresence->time,
                    'status' => 'out',
                    'status_by_admin' => Auth::user()->type == 'company' ? 'approved' : 'pending'
                ]);
            }
            $employeePresence->status = 'out';
        }

        if ($historyAttendance->status == 'out') {
            $employeePresence->status = 'in';
        }

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            Storage::putFileAs('/attendance/attachment/' . $request->get('user_id') . '/', $attachment, $employeePresence->created_at);
            $employeePresence->attachment = "/attendance/attachment/$request->get('user_id')/$employeePresence->created_at";
        }

        if (!$employeePresence->save()) {
            return new JsonBody([], 'Something went error on server, call administrator or HRD', 500);
        }

        return new JsonBody($employeePresence, 'Success');
    }
}
