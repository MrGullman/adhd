<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * The admin portal keeps up shared content: the gallery and the routine
 * templates. The app's policies are about families, so the portal's
 * resources allow admins instead. Each resource's query keeps family data out.
 */
trait AuthorizesAdmins
{
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $user = Auth::user();

        return $user instanceof User && $user->is_admin
            ? Response::allow()
            : Response::deny();
    }
}
