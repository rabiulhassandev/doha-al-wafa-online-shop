<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('shop:create-owner {email : The owner\'s email address} {--name= : The owner\'s display name}')]
#[Description('Create a store owner account for the admin panel')]
class CreateOwnerCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower((string) $this->argument('email'));

        $emailErrors = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255', Rule::unique(User::class)]],
        )->errors();

        if ($emailErrors->isNotEmpty()) {
            $this->components->error($emailErrors->first('email'));

            return self::FAILURE;
        }

        $name = $this->option('name') ?: text(label: 'Name', required: true);

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value): ?string => Validator::make(
                ['password' => $value],
                ['password' => [Password::defaults()]],
            )->errors()->first('password') ?: null,
        );

        $owner = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $owner->role = UserRole::Owner;
        $owner->save();

        $this->components->info("Owner account created for {$email}. Sign in at ".route('admin.login'));

        return self::SUCCESS;
    }
}
