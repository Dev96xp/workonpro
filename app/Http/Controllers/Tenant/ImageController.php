<?php

namespace App\Http\Controllers\Tenant;

use App\Models\BusinessImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class ImageController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp',
        ]);

        $tenantId  = tenant('id');
        $limit     = match (tenant('plan')) {
            'pro', 'enterprise' => 100,
            default             => 40,
        };

        if (BusinessImage::count() >= $limit) {
            return response()->json(['error' => "Has alcanzado el límite de {$limit} imágenes para tu plan."], 422);
        }

        $nombre    = Str::random(10) . $request->file('file')->getClientOriginalName();
        $directory = base_path("storage/app/public/tenants/{$tenantId}/images");
        $ruta      = "{$directory}/{$nombre}";
        $dbPath    = "tenants/{$tenantId}/images/{$nombre}";

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $originalSize = $request->file('file')->getSize();

        ImageManager::withDriver(new Driver())
            ->read($request->file('file'))
            ->scale(height: 1200)
            ->toWebp(80)
            ->save($ruta);

        BusinessImage::create([
            'filename'        => $nombre,
            'original_name'   => $request->file('file')->getClientOriginalName(),
            'path'            => $dbPath,
            'mime_type'       => 'image/webp',
            'size'            => $originalSize,
            'compressed_size' => filesize($ruta),
        ]);

        return response()->json(['success' => true]);
    }
}
