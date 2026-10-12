<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CurrentFamily;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adults without a family are sent to "Skapa familj" first. A separate admin
 * account has no family and goes to the admin portal instead.
 */
class EnsureFamilyMember
{
    public function __construct(private CurrentFamily $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->current->get() === null) {
            $user = $request->user();

            if ($user instanceof User && $user->is_admin) {
                return redirect()->to(Filament::getPanel('admin')->getUrl());
            }

            return redirect()->route('family.create');
        }

        return $next($request);
    }
}
