<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\RegisterDeviceTokenRequest;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;

class MobileDeviceTokenController extends Controller
{
    public function store(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        $validated = $request->validated();

        UserDeviceToken::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $validated['token'],
            ],
            [
                'platform' => $validated['platform'] ?? 'android',
                'device_name' => $validated['device_name'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Token perangkat berhasil didaftarkan.',
        ]);
    }
}
