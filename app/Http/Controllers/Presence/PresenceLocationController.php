<?php

namespace App\Http\Controllers\Presence;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\PresenceManagement\PresenceLocation;
use App\Models\UserManagement\CompanyGroup;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[Prefix('Presence/Location'), Name('presence-location'), Middleware('auth')]
class PresenceLocationController extends Controller
{
    #[Get('/', '.index')]
    public function index(Request $request)
    {
        return Inertia::render('Admin/PresenceLocation/PresenceLocation');
    }

    #[Get('/all/json', '.json.all')]
    public function getAllLocation(request $request)
    {
        $data = PresenceLocation::query()
            ->where('user_id', Auth::id())
            ->with('singleVerification')
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

    #[Get('/create', '.create')]
    public function detailIndex()
    {
        return Inertia::render('Admin/PresenceLocation/PresenceLocationDetail');
    }

    #[Get('/{id}', '.detail')]
    public function seeDetail(int $id, Request $request)
    {
        if ($id == null) {
            return redirect(route('presence-location.index'));
        }
        $data = PresenceLocation::query()->findOrFail($id);
        return Inertia::render('Admin/PresenceLocation/PresenceLocationDetail', ['dataFromServer' => $data]);
    }

    #[Post('/create', '.create')]
    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric',
            'max_hour' => 'required|numeric',
        ]);

        $data = PresenceLocation::query()->updateOrCreate([
            'id' => $request->get('id') ?? null,
        ], [
            'user_id' => $request->get('user_id'),
            'name' => $request->get('name'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'tolerance' => $request->get('radius'),
            'max_hour' => $request->get('max_hour'),
        ]);

        return new JsonBody($data, 'Data saved successfully');
    }


    #[Delete('/delete/json', '.json.delete')]
    public function deleteLocation(Request $request)
    {
        $request->validate(['id' => 'required|numeric|exists:presence_locations,id']);

        CompanyGroup::destroy($request->data_id);

        return new JsonBody(null, message: 'Account deleted successfully');
    }
}
