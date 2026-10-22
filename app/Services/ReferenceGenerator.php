<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReferenceGenerator
{
    public static function order(): string
    {
        return self::unique('orders', 'TH');
    }

    public static function request(): string
    {
        return self::unique('product_requests', 'PR');
    }

    public static function delivery(): string
    {
        return self::unique('delivery_tasks', 'DL');
    }

    private static function unique(string $table, string $prefix): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $body = '';
            for ($i = 0; $i < 6; $i++) {
                $body .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $reference = $prefix.'-'.now()->format('ymd').'-'.$body;
        } while (DB::table($table)->where('reference', $reference)->exists());

        return $reference;
    }
}
