<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Notification\Http\Resources\NotificationResource;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $company = $currentCompany->company();
        $branch = $currentCompany->hasBranch() ? $currentCompany->branch() : null;
        /** @var User|null $user */
        $user = $request->user();

        // Sincronizar alertas operativas inteligentes
        $this->notificationService->syncSmartAlerts($company, $branch);

        $limit = max(1, min((int) $request->query('limit', 20), 50));
        $notifications = $this->notificationService->list($company, $user, $limit);
        $unreadCount = $this->notificationService->unreadCount($company, $user);

        return ApiResponse::success(
            NotificationResource::collection($notifications),
            null,
            200,
            ['unread_count' => $unreadCount]
        );
    }

    public function unreadCount(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $company = $currentCompany->company();
        /** @var User|null $user */
        $user = $request->user();

        return ApiResponse::success([
            'unread_count' => $this->notificationService->unreadCount($company, $user),
        ]);
    }

    public function markAsRead(string $publicId, CurrentCompany $currentCompany): JsonResponse
    {
        $company = $currentCompany->company();
        $success = $this->notificationService->markAsRead($company, $publicId);

        return ApiResponse::success(['marked' => $success]);
    }

    public function markAllAsRead(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $company = $currentCompany->company();
        /** @var User|null $user */
        $user = $request->user();

        $count = $this->notificationService->markAllAsRead($company, $user);

        return ApiResponse::success(['updated_count' => $count]);
    }

    public function destroy(string $publicId, CurrentCompany $currentCompany): JsonResponse
    {
        $company = $currentCompany->company();
        $deleted = $this->notificationService->delete($company, $publicId);

        return ApiResponse::success(['deleted' => $deleted]);
    }
}
