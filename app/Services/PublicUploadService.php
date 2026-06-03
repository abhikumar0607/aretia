<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PublicUploadService
{
    public const MAX_BYTES = 50 * 1024 * 1024; // 50 MB

    public const MAX_MB = 50;

    /** @var list<string> */
    public const TYPES = ['kyc', 'orders', 'cases', 'reports', 'avatars'];

    public function decodeBase64(string $base64): string
    {
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                'data' => 'Invalid file. Please try again.',
            ]);
        }

        $this->assertMaxSize($binary);

        return $binary;
    }

    public function assertMaxSize(string $binary): void
    {
        if (strlen($binary) > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'data' => 'File must be '.self::MAX_MB.' MB or smaller.',
            ]);
        }
    }

    public function store(UploadedFile $file, string $type, int|string $id): string
    {
        $this->ensureDir($type, $id);
        $safeName = $this->safeFilename($file->getClientOriginalName());
        $file->move(public_path("uploads/{$type}/{$id}"), $safeName);

        return "uploads/{$type}/{$id}/{$safeName}";
    }

    public function storeBinary(string $binary, string $originalName, string $type, int|string $id): string
    {
        $this->assertMaxSize($binary);
        $this->ensureDir($type, $id);
        $safeName = $this->safeFilename($originalName);
        $relative = "uploads/{$type}/{$id}/{$safeName}";
        file_put_contents(public_path($relative), $binary);

        return $relative;
    }

    /**
     * @return list<array{name: string, binary: string}>
     */
    public function unzipBinary(string $zipBinary, int $maxFiles = 50): array
    {
        $this->assertMaxSize($zipBinary);

        $tmpDir = public_path('tmp/uploads');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $tmpZip = $tmpDir.DIRECTORY_SEPARATOR.'zip_'.uniqid('', true).'.zip';
        file_put_contents($tmpZip, $zipBinary);

        $zip = new ZipArchive();
        $opened = $zip->open($tmpZip);
        if ($opened !== true) {
            @unlink($tmpZip);
            throw ValidationException::withMessages([
                'data' => 'Invalid ZIP file.',
            ]);
        }

        $files = [];
        $totalBytes = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (count($files) >= $maxFiles) {
                break;
            }

            $stat = $zip->statIndex($i);
            if (! $stat || empty($stat['name'])) {
                continue;
            }

            $name = (string) $stat['name'];
            if (str_ends_with($name, '/')) {
                continue; // directory
            }

            // Prevent zip slip and weird paths.
            $baseName = basename(str_replace('\\', '/', $name));
            if ($baseName === '' || $baseName === '.' || $baseName === '..') {
                continue;
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                continue;
            }

            $totalBytes += strlen($contents);
            if ($totalBytes > self::MAX_BYTES) {
                $zip->close();
                @unlink($tmpZip);
                throw ValidationException::withMessages([
                    'data' => 'ZIP contents must be '.self::MAX_MB.' MB or smaller in total.',
                ]);
            }

            $files[] = [
                'name' => $baseName,
                'binary' => $contents,
            ];
        }

        $zip->close();
        @unlink($tmpZip);

        if ($files === []) {
            throw ValidationException::withMessages([
                'data' => 'ZIP file is empty.',
            ]);
        }

        return $files;
    }

    public function absolutePath(string $storedPath): string
    {
        if (str_starts_with($storedPath, 'uploads/')) {
            return public_path($storedPath);
        }

        return Storage::disk('local')->path($storedPath);
    }

    public function exists(string $storedPath): bool
    {
        return is_file($this->absolutePath($storedPath));
    }

    public function download(string $storedPath, string $downloadName): BinaryFileResponse
    {
        $full = $this->absolutePath($storedPath);
        abort_unless(is_file($full), 404);

        return response()->download($full, $downloadName);
    }

    public function delete(string $storedPath): void
    {
        $full = $this->absolutePath($storedPath);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    public function ensureDir(string $type, int|string $id): void
    {
        $dir = public_path("uploads/{$type}/{$id}");
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function ensureRootDirs(): void
    {
        foreach (self::TYPES as $type) {
            $dir = public_path("uploads/{$type}");
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function safeFilename(string $name): string
    {
        return time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    }
}
