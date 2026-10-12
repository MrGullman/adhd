<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * A separate admin account for the admin portal, without a family. Created on
 * the server only, since registration is closed.
 */
#[Signature('admin:create')]
#[Description('Skapar ett eget adminkonto för adminportalen, utan familj')]
class CreateAdminCommand extends Command
{
    use PasswordValidationRules, ProfileValidationRules;

    public function handle(): int
    {
        $input = [
            'name' => text('Namn', default: 'Admin', required: true),
            'email' => text('E-post', required: true),
            'password' => password('Lösenord (minst 8 tecken)', required: true),
        ];
        $input['password_confirmation'] = password('Samma lösenord igen', required: true);

        $validator = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
        // The account is made by whoever runs the server, so the e-mail counts as verified.
        $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        $this->info("Adminkontot {$admin->email} är skapat. Logga in på ".url('/login').', så kommer du till adminportalen.');
        $this->line('Slå gärna på tvåstegsverifiering under Inställningar när du är inloggad.');

        return self::SUCCESS;
    }
}
