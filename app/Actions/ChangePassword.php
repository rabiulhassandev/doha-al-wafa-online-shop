<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChangePassword
{
    public function handle(User $user, #[\SensitiveParameter] string $password, ?string $currentSessionId = null): void
    {
        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        // Remove database sessions created before password fingerprints were stored.
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))
                ->where('user_id', $user->getKey())
                ->when($currentSessionId !== null, fn ($query) => $query->where('id', '!=', $currentSessionId))
                ->delete();
        }
    }
}
