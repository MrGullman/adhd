<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The child's start page. Filled with the child's day in Milestone 5.
 */
class ChildHomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $child */
        $child = $request->user();

        return Inertia::render('child/Home', [
            'child' => [
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                'avatarUrl' => $child->avatarUrl(),
            ],
        ]);
    }
}
