<?php

namespace App\Http\Middleware;

use App\Enums\FamilyRole;
use App\Support\CurrentFamily;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: role:adult, role:editor, role:parent or role:child.
 * Children who reach an adult page are sent to their own start page.
 */
class EnsureRole
{
    public function __construct(private CurrentFamily $current) {}

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $current = $this->current->role();

        $allowed = match ($role) {
            // Without a family yet the user is an adult who is about to create one.
            'adult' => $current === null || $current->isAdult(),
            'editor' => $current?->canEdit() ?? false,
            'parent' => $current?->canManage() ?? false,
            'child' => $current === FamilyRole::Child,
            default => false,
        };

        if ($allowed) {
            return $next($request);
        }

        if ($current === FamilyRole::Child) {
            return redirect()->route('child.home');
        }

        abort(403);
    }
}
