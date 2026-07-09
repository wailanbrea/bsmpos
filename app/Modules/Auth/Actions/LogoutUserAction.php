<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Models\User;

final class LogoutUserAction
{
    public function execute(User $user): void
    {
        $user->audit('auth.logout');
        $user->currentAccessToken()->delete();
    }
}
