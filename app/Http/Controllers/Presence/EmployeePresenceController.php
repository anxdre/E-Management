<?php

namespace App\Http\Controllers\Presence;

use App\DateHelper;
use App\Exports\PresenceHistoryExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\PresenceManagement\PresenceLocation;
use App\Models\PresenceManagement\PresenceVerification;
use App\Models\UserManagement\User;
use App\PositionHelper;
use Dentro\Yalr\Attributes\Delete;
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
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

#[Prefix('Employee'), Name('employee-presence'), Middleware('auth')]
class EmployeePresenceController extends Controller
{
    use DateHelper, PositionHelper;

    #[Get('/{user}/Presence-History', '.index', ['scope-company'])]
    public function index(Request $request)
    {
        return Inertia::render('Employee/EmployeePresence/PresenceHistory');
    }

    #[Get('/{user}/json', '.json.all', ['scope-company'])]
    public function getAllHistory(request $request, User $user)
    {
        $request->validate(['mst_presence_location_id' => 'nullable|exists:mst_presence_locations,id',
            'time.*' => 'nullable|string',]);

        $data = PresenceEmployee::query()
            ->where('mst_user_id', $user->id)
            ->when(!empty($request->status), function ($query) use ($request) {
                return $query->where('status_by_admin', $request->get('status'));
            })
            ->when(!empty($request->location_id), function ($query) use ($request) {
                return $query->where('mst_presence_location_id', $request->get('location_id'));
            })
            ->orderBy('time_in', $request->orderBy ?? 'desc')
            ->paginate(10, page: $request->currentPage ?? 1)
            ->withQueryString();

        return new JsonBody($data);
    }

    #[Delete('/{user}/delete/json', '.json.delete', ['scope-company', 'only-company'])]
    public function deleteData(request $request, User $user)
    {
        $request->validate(['id' => 'required|numeric|exists:trx_presence_employees,id']);

        try {
            PresenceEmployee::query()->find($request->id)->delete();
        } catch (\Exception $exception) {
            return new JsonBody(null, message: 'Delete failed caused by ' . $exception->getMessage());
        }
        return new JsonBody(null, message: 'Presence successfuly deleted');
    }

    #[Post('{user}/create/json', '.json.create', ['scope-company'])]
    public function create(Request $request, User $user)
    {
        $request->validate([
            'code' => 'nullable|numeric',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_id' => 'required|exists:mst_presence_locations,id',
            'note' => 'nullable',
            'time' => 'nullable',
            'status' => ['required', Rule::in(['in', 'out'])],
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx',]);

        $verificationCode = PresenceVerification::query()
            ->select(['verification_hash', 'id'])
            ->where('mst_presence_location_id', $request->get('location_id'))
            ->first();

        if ($verificationCode == null && Auth::user()->isEmployee()) {
            return new JsonBody([], 'Verification code not been setup, Contact your administrator', 404);
        }

        if (Auth::user()->isEmployee() && $request->get('code') !== $verificationCode->verification_hash) {
            return new JsonBody([], 'Wrong verification code', 404);
        }

        $presenceLocation = PresenceLocation::query()->find($request->get('location_id'));

        $employeePresence = new PresenceEmployee([
            'mst_user_id' => $user->id,
            'mst_presence_location_id' => $request->get('location_id'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'note' => $request->get('note'),
            'status_by_admin' => Auth::user()->type == 'company' ? 'approved' : 'pending',
        ]);


        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $timestamp = now()->format('YmdHis');
            $filename = "$timestamp." . $attachment->getClientOriginalExtension();

            // Simpan ke public storage path
            $path = "attendance/attachment/{$user->id}";
            Storage::putFileAs("public/$path", $attachment, $filename);

            // Simpan path ke DB (tanpa "public/")
            $employeePresence->attachment = "$path/$filename";
        }

        $employeePresence->time_in = Carbon::parse($request->time_in)->timezone('Asia/Jakarta');
        $employeePresence->time_out = Carbon::parse($request->time_out)->timezone('Asia/Jakarta');
//        $employeePresence->presence_verification_id = $verificationCode->id;


        $isLimit = false;
        $isEarlier = false;
        if ($presenceLocation->max_hour) {
            $isLimit = Carbon::parse($employeePresence->time_out)->diffInHours(Carbon::parse($employeePresence->time_out), true) > $presenceLocation->max_hour;
        }

        if ($presenceLocation->min_hour) {
            $isEarlier = Carbon::parse($employeePresence->time_out)->diffInHours(Carbon::parse($employeePresence->time_out)) < $presenceLocation->min_hour;
        }

        if ($isLimit) {
            $employeePresence->extended_time = Carbon::parse($this->diffTimeFormatted(Carbon::parse($presenceLocation->max_hour), Carbon::parse($employeePresence->time_out)));
        }

        if ($isEarlier) {
            $employeePresence->extended_time = Carbon::parse($this->diffTimeMinusFormatted(Carbon::parse($presenceLocation->max_hour), Carbon::parse($employeePresence->time_out)));
        }

        if (!$employeePresence->save()) {
            return new JsonBody([], 'Something went error on server, call administrator or HRD', 500);
        }

        return new JsonBody($employeePresence, 'Success');
    }

    #[Get('/{user}/export/excel', '.export.excel', ['scope-company'])]
    public function exportExcel(Request $request, User $user)
    {
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
