<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicUploadService
{
    public const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

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
                'data' => 'File must be 5 MB or smaller.',
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
