<?php

namespace App\Support;

use App\Services\PublicUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class OrderDocumentUploadRules
{
    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'];

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'documents' => ['nullable', 'array'],
            'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.data' => ['required_with:documents', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => [
                'file',
                'max:'.(PublicUploadService::MAX_MB * 1024),
                self::allowedFileRule(),
            ],
        ];
    }

    private static function allowedFileRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $ext = strtolower($value->getClientOriginalExtension());
            if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                $fail('Only PDF, Word, images, and ZIP files are allowed.');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<array{name: string, data?: string, binary?: string}>
     */
    public static function resolvePayloads(Request $request, array $validated): array
    {
        if (! empty($validated['documents'])) {
            return array_values($validated['documents']);
        }

        return self::payloadsFromUploadedFiles($request->file('attachments', []));
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     * @return list<array{name: string, binary: string}>
     */
    public static function payloadsFromUploadedFiles(array $files): array
    {
        /** @var PublicUploadService $uploads */
        $uploads = app(PublicUploadService::class);
        $payloads = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $binary = file_get_contents($file->getRealPath());
            if ($binary === false) {
                continue;
            }

            $name = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext === 'zip') {
                foreach ($uploads->unzipBinary($binary) as $inner) {
                    $payloads[] = [
                        'name' => $inner['name'],
                        'binary' => $inner['binary'],
                    ];
                }

                continue;
            }

            $payloads[] = [
                'name' => $name,
                'binary' => $binary,
            ];
        }

        return $payloads;
    }
}
