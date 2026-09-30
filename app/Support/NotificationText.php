<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

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
        if (app()->getLocale() === 'en') {
            foreach (['priority', 'status'] as $enumParameter) {
                if (isset($params[$enumParameter]) && Lang::has($params[$enumParameter])) {
                    $params[$enumParameter] = __($params[$enumParameter]);
                }
            }
        }
        $title = $data['title'] ?? '';
        $message = $data['message'] ?? '';

        if (isset($data['title_key'])) {
            $title = __($data['title_key'], $params);
        } elseif (app()->getLocale() === 'en' && $title !== '' && Lang::has($title)) {
            $title = __($title);
        }

        if (isset($data['message_key'])) {
            $message = __($data['message_key'], $params);
        } elseif (app()->getLocale() === 'en') {
            $message = self::translateLegacyMessage((string) $message, $data['type'] ?? '');
        }

        return array_merge($data, [
            'title' => $title,
            'message' => $message,
        ]);
    }

    private static function translateLegacyMessage(string $message, string $type): string
    {
        if ($type === 'new_request' && preg_match('/^(.+?) mengajukan (.+) \(([^()]*)\)\.$/u', $message, $matches)) {
            return $matches[1].' requested '.$matches[2].' ('.__($matches[3]).').';
        }

        return match ($type) {
            'approved' => self::replaceSuffix(
                self::replacePrefix($message, 'Permintaan ', 'Request '),
                ' telah disetujui HR dan siap dibelanjakan.',
                ' was approved by HR and is ready for purchase.',
            ),
            'rejected' => self::translateLegacyReviewMessage($message, ' ditolak HR. Alasan: ', ' was rejected by HR. Reason: ', ' ditolak HR.', ' was rejected by HR.'),
            'delayed' => self::translateLegacyReviewMessage($message, ' ditunda oleh HR (Pending). Catatan: ', ' was delayed by HR (Pending). Note: ', ' ditunda oleh HR (Pending).', ' was delayed by HR (Pending).'),
            'completed' => self::replaceSuffix(
                self::replacePrefix($message, 'Barang ', 'Item '),
                ' telah diterima Gudang.',
                ' was received by the warehouse.',
            ),
            'closed', 'cancelled' => self::translateLegacyClosedMessage($message),
            default => $message,
        };
    }

    private static function translateLegacyClosedMessage(string $message): string
    {
        $message = self::replaceFirst(
            self::replaceFirst(
                self::replacePrefix($message, 'Sisa ', 'Remaining '),
                ' dari ',
                ' of ',
            ),
            ' ditutup. Status:',
            ' closed. Status: ',
        );
        $message = self::replaceFirst($message, 'Status: Ditutup Sebagian', 'Status: '.__('Ditutup Sebagian'));
        $message = self::replaceFirst($message, 'Status: Dibatalkan', 'Status: '.__('Dibatalkan'));

        return self::replaceFirst($message, ' Alasan: ', ' Reason: ');
    }

    private static function translateLegacyReviewMessage(string $message, string $withNote, string $translatedWithNote, string $withoutNote, string $translatedWithoutNote): string
    {
        $message = self::replacePrefix($message, 'Permintaan ', 'Request ');
        $message = self::replaceFirst($message, $withNote, $translatedWithNote);

        return self::replaceSuffix($message, $withoutNote, $translatedWithoutNote);
    }

    private static function replacePrefix(string $text, string $search, string $replace): string
    {
        return str_starts_with($text, $search) ? $replace.substr($text, strlen($search)) : $text;
    }

    private static function replaceSuffix(string $text, string $search, string $replace): string
    {
        return str_ends_with($text, $search) ? substr($text, 0, -strlen($search)).$replace : $text;
    }

    private static function replaceFirst(string $text, string $search, string $replace): string
    {
        $position = strpos($text, $search);

        return $position === false
            ? $text
            : substr($text, 0, $position).$replace.substr($text, $position + strlen($search));
    }
}
