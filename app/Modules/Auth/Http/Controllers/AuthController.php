<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Models\User;
use App\Modules\Auth\Actions\LoginUserAction;
use App\Modules\Auth\Actions\LogoutUserAction;
use App\Modules\Auth\Actions\RegisterUserAction;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $session = $action->execute($request->validated());

        return $this->sessionResponse($session, 'Cuenta creada.', 201);
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $session = $action->execute($request->validated());

        return $this->sessionResponse($session, 'Sesión iniciada.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(new UserResource($user->load(['companies.branches', 'companies.businessType'])));
    }

    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->execute($user);

        return ApiResponse::success(message: 'Sesión cerrada.');
    }

    /** @param array{user: User, token: string} $session */
    private function sessionResponse(array $session, string $message, int $status = 200): JsonResponse
    {
        return ApiResponse::success([
            'user' => new UserResource($session['user']->load(['companies.branches', 'companies.businessType'])),
            'token' => $session['token'],
        ], $message, $status);
    }
}
