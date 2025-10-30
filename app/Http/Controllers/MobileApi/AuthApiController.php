<?php

namespace App\Http\Controllers\MobileApi;

use App\Http\Controllers\Controller;
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
class AuthApiController extends Controller
{
    use MobileDecryptor;

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

}
