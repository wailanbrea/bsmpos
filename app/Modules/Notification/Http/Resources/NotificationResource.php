<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Resources;

use App\Modules\Notification\Models\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SystemNotification */
final class NotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'type' => $this->type,
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->action_url,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_ago' => $this->created_at?->diffForHumans(),
        ];
    }
}
