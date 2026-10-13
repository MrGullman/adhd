<?php

namespace App\Http\Middleware;

use App\Models\Family;
use App\Models\User;
use App\Support\CurrentFamily;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A suspended account, or everyone in a suspended family, is logged out and
 * sees why. An adult who is also in another family continues there. The
 * family's devices stop working too. Admin accounts are never suspended.
 */
class EnsureAccountActive
{
    public function __construct(private CurrentFamily $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('suspended', 'logout')) {
            return $next($request);
        }

        $user = $request->user();
        $family = $this->current->get();

        if ($user instanceof User) {
            if ($user->is_admin) {
                return $next($request);
            }

            if ($user->isSuspended()) {
                return $this->logOut($request);
            }

            if ($family?->isSuspended()) {
                $other = $user->families()->whereNull('families.suspended_at')->first();

                if ($other instanceof Family) {
                    $user->forceFill(['active_family_id' => $other->id])->save();

                    return redirect()->route('dashboard');
                }

                return $this->logOut($request);
            }
        } elseif ($family?->isSuspended() && $request->routeIs('child-login', 'child-login.store')) {
            return redirect()->route('suspended');
        }

        return $next($request);
    }

    private function logOut(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('suspended');
    }
}
