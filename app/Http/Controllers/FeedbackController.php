<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Support\CurrentFamily;
use Illuminate\Http\JsonResponse;

/**
 * Saves "Tyck till" with the page it was written on. Admins read it in the
 * admin portal.
 */
class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request, CurrentFamily $current): JsonResponse
    {
        $feedback = new Feedback;
        $feedback->forceFill([
            'user_id' => $request->user()?->id,
            'family_id' => $current->id(),
            'page' => $request->string('page')->toString(),
            'url' => $request->string('url')->toString(),
            'message' => trim($request->string('message')->toString()),
        ])->save();

        return response()->json(['id' => $feedback->id], 201);
    }
}
