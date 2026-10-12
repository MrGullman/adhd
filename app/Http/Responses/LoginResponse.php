<?php

namespace App\Http\Responses;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Where to go after logging in. The login page is an Inertia page, and
 * Inertia can only follow a redirect to another Inertia page. The admin
 * portal is not one, so it is opened with a full page load. Pages a user may
 * not open, and addresses that are not pages (e.g. pictures), are skipped.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        /** @var User $user */
        $user = $request->user();
        // A separate admin account has no family: its home is the admin portal.
        $home = $user->is_admin && ! $user->families()->exists()
            ? Filament::getPanel('admin')->getUrl()
            : url(Fortify::redirects('login'));
        $intended = $request->session()->pull('url.intended');
        $target = is_string($intended) && $this->mayOpen($user, $intended) ? $intended : $home;

        if ($this->isAdminPortal($target)) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }

    private function mayOpen(User $user, string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($this->isAdminPortal($url)) {
            return $user->is_admin;
        }

        // Pictures, the picture picker's data and Livewire are not pages.
        return ! preg_match('#^(bilder|livewire)#', $path);
    }

    private function isAdminPortal(string $url): bool
    {
        $admin = trim((string) parse_url(Filament::getPanel('admin')->getUrl(), PHP_URL_PATH), '/');
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path === $admin || str_starts_with($path, $admin.'/');
    }
}
