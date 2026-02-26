<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function notify(
        int $userId,
        string $type,
        string $message,
        array $data = []
    ): void {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function notifyMany(array $userIds, string $type, string $message, array $data = [])
    {
        foreach ($userIds as $id) {
            $this->notify($id, $type, $message, $data);
        }
    }
}
