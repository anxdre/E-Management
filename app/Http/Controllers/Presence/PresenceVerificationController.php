<?php

namespace App\Http\Controllers\Presence;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\PresenceManagement\PresenceVerification;
use Carbon\CarbonImmutable;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;

#[Prefix('Presence/Location/Verification'), Name('presence-verification'), Middleware('auth')]
class PresenceVerificationController extends Controller
{
    #[Post('/', '.verify')]
    public function index(Request $request)
    {
        $request->validate(['id' => 'required|exists:mst_presence_locations,id',
            'code'=>'required|number',
            'latitude'=>'required|numeric',
            'longitude'=>'required|numeric',
            'note'=>'nullable|string',
            'attachment'=>'nullable|string',]);
    }

    #[Post('/New', '.json.new')]
    public function generateNew(Request $request)
    {
        $request->validate(['id' => 'required|exists:mst_presence_locations,id']);
        $date = CarbonImmutable::now('Asia/Jakarta')->format('d');
        $second = CarbonImmutable::now('Asia/Jakarta')->format('s');
        $randPrefix = rand(10, 99);
        $verificationCode = PresenceVerification::query()->create([
            'mst_presence_location_id' => $request->get('id'),
            'verification_hash' => "$randPrefix$date$second"
        ]);
        return new JsonBody([$verificationCode],'new verification code, generated');
    }
}
