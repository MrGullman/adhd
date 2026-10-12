<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Admins keep up the shared picture gallery on /admin. Given on the server only.
 */
#[Signature('users:admin {email : E-postadressen} {--remove : Ta bort admin i stället}')]
#[Description('Gör en vuxen till admin för adminportalen, eller tar bort admin')]
class GrantAdminCommand extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Det finns ingen användare med den e-postadressen.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('remove')])->save();

        $this->info($user->is_admin
            ? "{$user->name} är admin. Adminportalen finns på ".url('/admin').'.'
            : "{$user->name} är inte längre admin.");

        return self::SUCCESS;
    }
}
