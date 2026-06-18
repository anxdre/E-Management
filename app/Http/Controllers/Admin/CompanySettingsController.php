<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\CompanyProfile;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

#[Prefix('Company/Settings'), Name('company-settings'), Middleware(['auth', 'only-company'])]
class CompanySettingsController extends Controller
{
    #[Get('/', '.index')]
    public function index()
    {
        $profile = CompanyProfile::first();

        return Inertia::render('Admin/Company/CompanySettings', [
            'profile' => $profile,
        ]);
    }

    #[Post('/update/json', '.json.update')]
    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'npwp' => 'nullable|string|max:30',
            'company_logo' => ['sometimes', File::image()->max(15 * 1024)],
            'auto_approve' => 'boolean',
            'auto_approve_mode' => 'in:realtime,cron',
            'auto_approve_batch_hour' => 'nullable|date_format:H:i',
            'auto_approve_min_duration' => 'nullable|integer|min:0',
            'auto_approve_duplicate_coords' => 'boolean',
        ]);

        $profile = CompanyProfile::first();

        if (!$profile) {
            $profile = CompanyProfile::query()->create([
                'company_name' => $request->company_name,
                'company_phone' => $request->company_phone,
                'company_address' => $request->company_address,
                'company_email' => $request->company_email,
                'npwp' => $request->npwp,
                'auto_approve' => $request->boolean('auto_approve'),
                'auto_approve_mode' => $request->auto_approve_mode ?? 'realtime',
                'auto_approve_batch_hour' => $request->auto_approve_batch_hour ?? '17:00:00',
                'auto_approve_min_duration' => $request->auto_approve_min_duration ?? 60,
                'auto_approve_duplicate_coords' => $request->boolean('auto_approve_duplicate_coords'),
            ]);

            return new JsonBody($profile, message: 'Company profile created successfully');
        }

        $profile->update([
            'company_name' => $request->company_name,
            'company_phone' => $request->company_phone,
            'company_address' => $request->company_address,
            'company_email' => $request->company_email,
            'npwp' => $request->npwp,
            'auto_approve' => $request->boolean('auto_approve'),
            'auto_approve_mode' => $request->auto_approve_mode ?? $profile->auto_approve_mode,
            'auto_approve_batch_hour' => $request->auto_approve_batch_hour ?? $profile->auto_approve_batch_hour,
            'auto_approve_min_duration' => $request->auto_approve_min_duration ?? $profile->auto_approve_min_duration,
            'auto_approve_duplicate_coords' => $request->boolean('auto_approve_duplicate_coords'),
        ]);

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('company_logos', 'public');
            $profile->update(['company_logo' => $path]);
        }

        return new JsonBody(null, message: 'Company profile updated successfully');
    }

    #[Post('/change-password/json', '.json.change-password')]
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return new JsonBody(null, message: 'Password changed successfully');
    }

    #[Post('/change-email/json', '.json.change-email')]
    public function changeEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('mst_users', 'email')->ignore(Auth::id())],
            'current_password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        $user->email = $request->email;
        $user->email_verified_at = null;
        $user->save();

        return new JsonBody(null, message: 'Email changed successfully. Please verify your new email address.');
    }
}
