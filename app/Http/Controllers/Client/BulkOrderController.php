<?php

namespace App\Http\Controllers\Client;

use App\Exports\OrdersTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\OrdersImport;
use App\Services\OrderCreationService;
use App\Services\OrderDocumentService;
use App\Support\ExcelDownload;
use App\Support\OrderDocumentUploadRules;
use App\Support\SpreadsheetUploadRules;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class BulkOrderController extends Controller
{
    public function show(): View
    {
        $packages = \App\Models\ServicePackage::where('is_active', true)->orderBy('sort_order')->get();

        return view('client.orders.import', compact('packages'));
    }

    public function import(
        Request $request,
        OrderCreationService $orderService,
        OrderDocumentService $documentService,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'file' => SpreadsheetUploadRules::importFile(),
            ...OrderDocumentUploadRules::rules(),
        ]);

        $import = new OrdersImport(
            auth()->user(),
            false,
            $orderService,
            $documentService,
            OrderDocumentUploadRules::resolvePayloads($request, $validated),
        );

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return Toast::back('Import failed: '.$e->getMessage(), 'error');
        }

        $message = "{$import->imported} order(s) submitted for approval.";
        if (count($import->errors) > 0) {
            $message .= ' Some rows had errors.';
        }

        if (count($import->errors) > 0) {
            session()->flash('import_errors', $import->errors);
        }

        return Toast::to(route('client.orders.index'), $message);
    }

    public function template(): Response
    {
        return ExcelDownload::xlsx(
            new OrdersTemplateExport(forAdmin: false),
            'aretia-orders-template.xlsx'
        );
    }
}
