<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDocument;

class OrderDocumentService
{
    public function __construct(
        private PublicUploadService $uploads,
        private CaseOrderDocumentService $caseDocuments,
    ) {}

    /**
     * @param  list<array{name: string, data?: string, binary?: string}>  $documents
     */
    public function attachMany(Order $order, int $userId, array $documents): void
    {
        if ($documents === []) {
            return;
        }

        foreach ($documents as $doc) {
            if (isset($doc['binary'])) {
                $this->attachBinary($order, $userId, (string) $doc['name'], (string) $doc['binary']);
            } else {
                $this->attach($order, $userId, (string) $doc['name'], (string) ($doc['data'] ?? ''));
            }
        }

        $order->loadMissing('caseFile');
        if ($order->caseFile) {
            $this->caseDocuments->syncFromOrder($order->caseFile);
        }
    }

    public function attach(Order $order, int $userId, string $name, string $base64): OrderDocument
    {
        return $this->attachBinary($order, $userId, $name, $this->uploads->decodeBase64($base64));
    }

    public function attachBinary(Order $order, int $userId, string $name, string $binary): OrderDocument
    {
        $path = $this->uploads->storeBinary($binary, $name, 'orders', $order->id);

        return OrderDocument::create([
            'order_id' => $order->id,
            'uploaded_by' => $userId,
            'original_name' => $name,
            'path' => $path,
        ]);
    }
}
