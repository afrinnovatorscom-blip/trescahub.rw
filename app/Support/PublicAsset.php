<?php

namespace App\Support;

use App\Models\ShopProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class PublicAsset
{
    public static function store(UploadedFile $file, string $folder): string
    {
        $destination = public_path('assets/'.$folder);

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $extension = strtolower($file->guessExtension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $filename = uniqid('', true).'.'.$extension;
        $file->move($destination, $filename);

        return $folder.'/'.$filename;
    }

    public static function delete(?string $path): void
    {
        $path = $path ? preg_replace('#^(public/)?assets/#', '', ltrim($path, '/')) : null;

        if ($path && ! str_contains($path, '..') && File::exists(public_path('assets/'.$path))) {
            File::delete(public_path('assets/'.$path));
        }
    }

    public static function deleteIfUnreferenced(?string $path): void
    {
        if (! $path) {
            return;
        }

        $stillUsed = ShopProduct::query()
            ->where('thumbnail', $path)
            ->orWhereJsonContains('images', $path)
            ->orWhereJsonContains('pending_payload->thumbnail', $path)
            ->orWhereJsonContains('pending_payload->images', $path)
            ->exists();

        if (! $stillUsed) {
            self::delete($path);
        }
    }
}
