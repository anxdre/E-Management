<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\UserManagement\CompanyGroup;
use App\Models\UserManagement\GroupHasUser;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Put;
use Illuminate\Http\Request;
use Inertia\Inertia;

#[Prefix('Employee/Group'), Name('employee-group'), Middleware('auth')]
class EmployeeGroupController extends Controller
{
    #[Get('/', '.index')]
    public function index()
    {
        return Inertia::render('Admin/EmployeeGroup/EmployeeGroup');
    }

    #[Get('/all/json', '.json.all')]
    public function getAllEmployeeGroup(request $request)
    {
        $data = CompanyGroup::query()
//            ->where('mst_user_id', Auth::id())
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%");
            })
            ->paginate(10)
            ->withQueryString();

        array_map(function ($item) {
            $item['total_employee'] = GroupHasUser::query()
                ->where('mst_company_group_id', $item['id'])
                ->count();
            return $item;
        }, $data->items());
        return new JsonBody($data);
    }

    #[Post('/add/json', '.json.add')]
    public function addGroup(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        CompanyGroup::query()->create([
            'name' => $request->name,
        ]);

        return new JsonBody(null, message: 'Group added successfully');
    }

    #[Put('/update/json', '.json.update')]
    public function updateGroup(Request $request)
    {
        $request->validate(['name' => 'required|string',
            'id' => 'required|numeric|exists:mst_company_groups,id']);

        CompanyGroup::query()->find($request->get('id'))->update([
            'name' => $request->get('name')
        ]);

        return new JsonBody(null, message: 'Group updated successfully');
    }

    #[Delete('/delete/json', '.json.delete')]
    public function deleteGroup(Request $request)
    {
        $request->validate(['data_id' => 'required|numeric|exists:mst_company_groups,id']);

        CompanyGroup::destroy($request->data_id);

        return new JsonBody(null, message: 'Group deleted successfully');
    }
}
