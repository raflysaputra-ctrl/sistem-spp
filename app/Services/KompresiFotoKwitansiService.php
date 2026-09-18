<?php

namespace App\Services;

use App\Models\Siswa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KompresiFotoKwitansiService
{
    private const MAX_SIZE = 2 * 1024 * 1024;

    /**
     * @return array{path: string, mime_type: string, ukuran_file: int}
     */
    public function simpan(UploadedFile $file, Siswa $siswa): array
    {
        $mimeType = $file->getMimeType();
        $image = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($file->getRealPath()),
            'image/png' => imagecreatefrompng($file->getRealPath()),
            default => false,
        };

        if (! $image) {
            throw ValidationException::withMessages([
                'foto' => 'Foto kwitansi tidak dapat diproses.',
            ]);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'kwitansi-');

        try {
            if ($mimeType === 'image/jpeg') {
                $image = $this->orientasikanJpeg($image, $file->getRealPath());
                imagejpeg($image, $temporaryPath, 80);
                $extension = 'jpg';
            } else {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagepng($image, $temporaryPath, 6);
                $extension = 'png';
            }

            $ukuranFile = filesize($temporaryPath);

            if ($ukuranFile === false || $ukuranFile > self::MAX_SIZE) {
                throw ValidationException::withMessages([
                    'foto' => 'Hasil kompresi foto masih melebihi batas 2 MB. Gunakan foto dengan resolusi lebih kecil.',
                ]);
            }

            $path = 'kwitansi-siswa/'.$siswa->id_siswa.'/'.Str::uuid().'.'.$extension;
            $stream = fopen($temporaryPath, 'r');
            $tersimpan = $stream && Storage::disk('local')->put($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $tersimpan) {
                throw ValidationException::withMessages([
                    'foto' => 'Foto kwitansi tidak dapat disimpan. Silakan coba lagi.',
                ]);
            }

            return [
                'path' => $path,
                'mime_type' => $mimeType,
                'ukuran_file' => $ukuranFile,
            ];
        } finally {
            imagedestroy($image);

            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function orientasikanJpeg(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = exif_read_data($path)['Orientation'] ?? null;
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
