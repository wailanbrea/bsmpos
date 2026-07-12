<?php

declare(strict_types=1);

namespace App\Core\Support;

use RuntimeException;

/**
 * Normaliza una imagen a un cuadrado de lado fijo (estilo catálogo) usando GD,
 * sin dependencias externas. Recorta al centro para conservar la proporción y
 * llenar todo el cuadro (cover), y reescala al tamaño indicado.
 *
 * Devuelve el binario listo para guardar y la extensión resultante.
 */
final class SquareImage
{
    /**
     * @return array{binary: string, extension: string}
     */
    public static function fit(string $sourcePath, string $mime, int $size = 600): array
    {
        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => throw new RuntimeException('Formato de imagen no soportado.'),
        };

        if ($source === false) {
            throw new RuntimeException('No se pudo leer la imagen.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $srcX = intdiv($width - $side, 2);
        $srcY = intdiv($height - $side, 2);

        $canvas = imagecreatetruecolor($size, $size);
        if ($canvas === false) {
            imagedestroy($source);
            throw new RuntimeException('No se pudo crear el lienzo de la imagen.');
        }

        // Conservar transparencia para PNG/WebP.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefill($canvas, 0, 0, $transparent);
        }

        imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $size, $size, $side, $side);
        imagedestroy($source);

        [$binary, $extension] = self::encode($canvas, $mime);
        imagedestroy($canvas);

        return ['binary' => $binary, 'extension' => $extension];
    }

    /**
     * @param  \GdImage  $canvas
     * @return array{0: string, 1: string}
     */
    private static function encode($canvas, string $mime): array
    {
        ob_start();

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'png',
        };

        match ($mime) {
            'image/jpeg' => imagejpeg($canvas, null, 85),
            'image/png' => imagepng($canvas, null, 6),
            'image/webp' => imagewebp($canvas, null, 85),
            default => imagepng($canvas),
        };

        $binary = (string) ob_get_clean();

        return [$binary, $extension];
    }
}
