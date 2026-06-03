<?php

namespace App\Services;

use App\Models\CaseFile;
use App\Models\Document;
use App\Models\OrderDocument;

class CaseOrderDocumentService
{
    public function __construct(private PublicUploadService $uploads) {}

    public function syncFromOrder(CaseFile $case): void
    {
        $case->loadMissing('order.documents');
        $order = $case->order;
        if (! $order || $order->documents->isEmpty()) {
            return;
        }

        foreach ($order->documents as $orderDoc) {
            if ($this->alreadySynced($case, $orderDoc)) {
                continue;
            }

            $full = $this->uploads->absolutePath($orderDoc->path);
            if (! is_file($full)) {
                continue;
            }

            $binary = file_get_contents($full);
            if ($binary === false) {
                continue;
            }

            $path = $this->uploads->storeBinary($binary, $orderDoc->original_name, 'cases', $case->id);

            Document::create([
                'documentable_type' => CaseFile::class,
                'documentable_id' => $case->id,
                'uploaded_by' => $orderDoc->uploaded_by,
                'type' => 'order',
                'category' => 'order submission',
                'original_name' => $orderDoc->original_name,
                'path' => $path,
            ]);
        }
    }

    private function alreadySynced(CaseFile $case, OrderDocument $orderDoc): bool
    {
        return $case->documents()
            ->where('type', 'order')
            ->where('original_name', $orderDoc->original_name)
            ->exists();
    }
}
