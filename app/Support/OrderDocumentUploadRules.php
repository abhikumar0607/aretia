<?php

namespace App\Support;

use App\Services\PublicUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class OrderDocumentUploadRules
{
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
            'attachments.*' => ['file', 'max:'.(PublicUploadService::MAX_MB * 1024)],
        ];
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
    private static function payloadsFromUploadedFiles(array $files): array
    {
        $payloads = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $binary = file_get_contents($file->getRealPath());
            if ($binary === false) {
                continue;
            }

            $payloads[] = [
                'name' => $file->getClientOriginalName(),
                'binary' => $binary,
            ];
        }

        return $payloads;
    }
}
