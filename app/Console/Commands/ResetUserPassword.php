<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password as secretPrompt;

/**
 * Reset a staff password safely, and say why they still cannot log in.
 *
 * Doubles as a diagnostic on purpose. Filament reports a canAccessPanel()
 * refusal with the SAME error as a wrong password, so "the password is wrong"
 * is frequently not a password problem at all — this command resets the
 * password AND reports the account's roles and panel access, which is the
 * question that actually needs answering.
 */
class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email : The account to reset}';

    protected $description = 'Reset a user password (hidden prompt) and report their panel access';

    public function handle(): int
    {
        // Normalised the same way User::setEmailAttribute() normalises on write,
        // so a copy-pasted address with a trailing space still finds the row.
        $email = strtolower(trim((string) $this->argument('email')));

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");

            $similar = User::query()
                ->where('email', 'like', '%' . substr($email, 0, max(3, (int) (strlen($email) / 2))) . '%')
                ->limit(5)
                ->pluck('email');

            if ($similar->isNotEmpty()) {
                $this->line('Did you mean one of these?');
                $similar->each(fn (string $candidate) => $this->line('  - ' . $candidate));
            }

            return self::FAILURE;
        }

        $this->line("Resetting password for <info>{$user->name}</info> <{$user->email}>");

        $plain = secretPrompt(label: 'New password', required: true);
        $again = secretPrompt(label: 'Confirm new password', required: true);

        if ($plain !== $again) {
            $this->error('Passwords do not match. Nothing was changed.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['password' => $plain],
            ['password' => ['required', Password::min(8)]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->get('password') as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        // Assigned in PLAIN and saved once. The User model casts
        // 'password' => 'hashed', so the hashing happens exactly here. Calling
        // Hash::make() first would also work — Laravel's hashed cast checks
        // Hash::isHashed() and passes an already-hashed value through — but
        // relying on the cast keeps one hashing path for the whole app.
        $user->password = $plain;
        $user->save();

        $this->info('Password updated.');

        // Prove it round-trips rather than trusting that it did.
        $this->line($user->fresh()->password && Hash::check($plain, $user->fresh()->password)
            ? '  <info>verified:</info> the new password authenticates'
            : '  <error>WARNING:</error> the stored hash does not match the new password');

        $this->newLine();
        $this->line('Account diagnostics:');

        $roles = $user->getRoleNames();

        $this->line('  roles: ' . ($roles->isEmpty() ? '(none)' : $roles->implode(', ')));

        $canAccess = $roles->intersect(User::PANEL_ROLES)->isNotEmpty()
            || $user->email === 'victorynonso9@gmail.com';

        if ($canAccess) {
            $this->line('  panel access: <info>yes</info>');
        } else {
            // This is the usual answer when someone reports "wrong password".
            $this->line('  panel access: <error>NO</error>');
            $this->warn('  This account can authenticate but will be refused the /admin panel,');
            $this->warn('  which Filament reports as a credentials error — it looks like a wrong');
            $this->warn('  password but is not. Assign one of: ' . implode(', ', User::PANEL_ROLES));
        }

        return self::SUCCESS;
    }
}
