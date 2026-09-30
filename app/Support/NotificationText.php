<?php

namespace App\Support;

class NotificationText
{
    /**
     * Terjemahkan payload notifikasi database (title_key/message_key + params)
     * menjadi title/message siap tampil. Notifikasi lama yang hanya menyimpan
     * title/message langsung tetap dikembalikan apa adanya.
     */
    public static function resolve(array $data): array
    {
        $params = is_array($data['params'] ?? null) ? $data['params'] : [];

        return array_merge($data, [
            'title' => isset($data['title_key']) ? __($data['title_key'], $params) : ($data['title'] ?? ''),
            'message' => isset($data['message_key']) ? __($data['message_key'], $params) : ($data['message'] ?? ''),
        ]);
    }
}
