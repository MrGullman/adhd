<?php

namespace App\Http\Middleware;

use App\Support\CurrentFamily;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adults without a family are sent to "Skapa familj" first.
 */
class EnsureFamilyMember
{
    public function __construct(private CurrentFamily $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->current->get() === null) {
            return redirect()->route('family.create');
        }

        return $next($request);
    }
}
