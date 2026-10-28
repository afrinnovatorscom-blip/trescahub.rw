<?php

namespace App\Services;

use App\Models\ShopProduct;
use App\Support\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductImageService
{
    public function withUploads(Request $request, array $validated, ?ShopProduct $product): array
    {
        $oldThumbnail = $product?->thumbnail;
        $oldImages = $product?->images ?? [];
        $galleryOrder = $request->boolean('gallery_order_present')
            ? ($validated['gallery_order'] ?? [])
            : array_map(fn ($path) => 'existing:'.base64_encode($path), $oldImages);
        $existingImages = [];
        $orderedUploadIndexes = [];

        foreach ($galleryOrder as $token) {
            if (str_starts_with($token, 'existing:')) {
                $image = base64_decode(substr($token, 9), true);
                if (! is_string($image) || ! in_array($image, $oldImages, true) || in_array($image, $existingImages, true)) {
                    throw ValidationException::withMessages([
                        'gallery_order' => 'The selected gallery contains an image that does not belong to this product.',
                    ]);
                }
                $existingImages[] = $image;
            } elseif (preg_match('/^upload:(\d+)$/', $token, $matches)) {
                $index = (int) $matches[1];
                if (isset($orderedUploadIndexes[$index])) {
                    throw ValidationException::withMessages(['gallery_order' => 'A new image was selected more than once.']);
                }
                $orderedUploadIndexes[$index] = true;
            } else {
                throw ValidationException::withMessages(['gallery_order' => 'The gallery order is invalid.']);
            }
        }

        $uploadedImages = $request->file('images', []);
        if (count($existingImages) + count($orderedUploadIndexes) > 8) {
            throw ValidationException::withMessages([
                'images' => 'A product can have up to eight additional images.',
            ]);
        }

        foreach ($orderedUploadIndexes as $index => $_) {
            if (! isset($uploadedImages[$index])) {
                throw ValidationException::withMessages(['gallery_order' => 'A selected gallery upload is missing.']);
            }
        }
        if (count($existingImages) + count($uploadedImages) > 8) {
            throw ValidationException::withMessages([
                'images' => 'A product can have up to eight additional images.',
            ]);
        }

        $newUploads = [];
        $obsoleteUploads = [];
        $thumbnail = $oldThumbnail;
        $images = [];

        try {
            foreach ($galleryOrder as $token) {
                if (str_starts_with($token, 'existing:')) {
                    $images[] = base64_decode(substr($token, 9), true);
                } else {
                    $index = (int) substr($token, 7);
                    $path = PublicAsset::store($uploadedImages[$index], 'shop-products/images');
                    $newUploads[] = $path;
                    $images[] = $path;
                }
            }

            foreach ($uploadedImages as $index => $image) {
                if (! isset($orderedUploadIndexes[$index])) {
                    $path = PublicAsset::store($image, 'shop-products/images');
                    $newUploads[] = $path;
                    $images[] = $path;
                }
            }

            if ($request->hasFile('thumbnail')) {
                $thumbnail = PublicAsset::store($request->file('thumbnail'), 'shop-products/thumbnails');
                $newUploads[] = $thumbnail;
            } elseif ($request->boolean('remove_thumbnail')) {
                $thumbnail = null;
            }
        } catch (Throwable $exception) {
            foreach ($newUploads as $path) {
                PublicAsset::delete($path);
            }

            throw $exception;
        }

        if ($oldThumbnail && $oldThumbnail !== $thumbnail && ! in_array($oldThumbnail, $images, true)) {
            $obsoleteUploads[] = $oldThumbnail;
        }
        $obsoleteUploads = array_merge($obsoleteUploads, array_diff($oldImages, $images));

        unset($validated['gallery_order'], $validated['gallery_order_present'], $validated['remove_thumbnail']);
        $validated['thumbnail'] = $thumbnail;
        $validated['images'] = array_values($images);

        return [$validated, array_values(array_unique($newUploads)), array_values(array_unique($obsoleteUploads))];
    }

    public function deleteUnreferenced(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            PublicAsset::deleteIfUnreferenced($path);
        }
    }
}
