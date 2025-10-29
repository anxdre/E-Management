<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\JsonBody;
use App\MobileDecryptor;
use App\Models\UserManagement\User;
use App\Models\UserManagement\UserDetail;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

#[Prefix('Auth'), Name('auth')]
class AuthController extends Controller
{
    use MobileDecryptor;

    #[Get('/sign-in', name: '.sign-in')]
    public function login(Request $request)
    {
        return Inertia::render('Auth/Authentication', [
            'status' => session('status'),
        ]);
    }

    #[Post('/sign-in', name: '.sign-in')]
    public function signIn(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->route('dashboard.index');

    }

    #[Post('api/sign-in/', name: '.api.sign-in')]
    public function apiSignIn(Request $request)
    {
        $request->validate([
            'device_name' => 'required|string',
            'device_token' => 'required|string',
            'device_type' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $dataToken = $this->decodeDeviceToken($request->device_token);
        if (!$dataToken || $dataToken['prefix'] !== env('APP_NAME')) {
            return response()->json(['message' => 'Invalid App credentials'], 401);
        }

        if ($dataToken['date'] !== Carbon::now()->format('Ymd')) {
            return response()->json(['message' => 'Invalid Token credentials'], 401);
        }

        $user = User::query()
            ->where('email', $request->email)
            ->where('id', $dataToken['user_id'])
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            sleep(1);
            return response()->json(['message' => 'Invalid User credentials'], 401);
        }

        $existingToken = $user->tokens()
            ->where('name', 'mobile-app')
            ->whereNull('expires_at') // token belum expired
            ->first();

        if ($existingToken) {
            return new JsonBody(null, message: 'Token already exists', status_code: 403);
        }

        $newToken = $user->createToken('mobile-app');
        $personalAccessToken = $user->tokens()->latest()->first();

        if (!$personalAccessToken) {
            return response()->json(['message' => 'Failed to create token'], 500);
        }

        $personalAccessToken->forceFill([
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'device_token' => $request->device_token,
            'expires_at' => null,
        ])->save();

        $data = [
            'token' => $newToken->plainTextToken,
            'user' => $user->load('userDetail'),
        ];

        return new JsonBody($data);
    }

    #[Delete('api/delete-device/{id}', name: '.api.delete-device', middleware: ['only-company', 'scope-company'])]
    public function forceLogoutDevice($id, Request $request)
    {
        $employee = User::findOrFail($id);

        $query = $employee->tokens();

        // kalau admin ingin logout semua device:
        if (!$request->device_token) {
            $query->delete();
        } else {
            // logout satu device aja
            $query->where('device_token', $request->device_token)->delete();
        }

        return response()->json([
            'message' => 'Employee logged out successfully.'
        ]);
    }


    #[Post('api/base-token/', name: '.api.base-token')]
    public function getBaseToken(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            sleep(1);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $prefix = env('APP_NAME');
        $date = Carbon::now()->format('Ymd');
        $token = encrypt($prefix . '-' . $date . '-' . $user->id);

        return new JsonBody($token);
    }

    #[Post('/sign-out', name: '.sign-out')]
    public function signOut(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    #[Post('/sign-up', name: '.sign-up')]
    public function signUp(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'phone' => 'required|min:10|unique:' . UserDetail::class,
            'address' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            $companyDetail = UserDetail::query()->create([
                'fullname' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            $user = User::query()->create(['name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_detail_id' => $companyDetail->id,
                'type' => 'company']);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }

        event(new Registered($user));

//        Auth::login($user);

        return redirect()->back();
    }
}
