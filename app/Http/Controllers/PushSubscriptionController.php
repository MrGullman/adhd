<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reminders\StorePushSubscriptionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Turns push reminders on or off for the device the user is on.
 */
class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->updatePushSubscription(
            $request->string('endpoint')->toString(),
            $request->string('keys.p256dh')->toString(),
            $request->string('keys.auth')->toString(),
            $request->filled('content_encoding') ? $request->string('content_encoding')->toString() : 'aesgcm',
        );

        return response()->json(['devices' => $user->pushSubscriptions()->count()], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Only the user's own devices; another's endpoint is left alone.
        $user->pushSubscriptions()->where('endpoint', $request->string('endpoint')->toString())->delete();

        return response()->json(['devices' => $user->pushSubscriptions()->count()]);
    }
}
