<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

trait AuthorizesApiRequest
{
    protected function authorizeApi(User $user, string $ability, mixed $arguments): void
    {
        if (! Gate::forUser($user)->allows($ability, $arguments)) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para realizar esta acción.', 403);
        }
    }
}
