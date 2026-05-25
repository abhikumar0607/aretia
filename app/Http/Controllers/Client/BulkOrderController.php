<?php

namespace App\Http\Controllers\Client;

use App\Exports\OrdersTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\OrdersImport;
use App\Services\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Toast;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkOrderController extends Controller
{
    public function show(): View
    {
        $packages = \App\Models\ServicePackage::where('is_active', true)->orderBy('sort_order')->get();

        return view('client.orders.import', compact('packages'));
    }

    public function import(Request $request, OrderCreationService $orderService): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new OrdersImport(auth()->user(), false, $orderService);
        Excel::import($import, $request->file('file'));

        $message = "{$import->imported} order(s) imported successfully.";
        if (count($import->errors) > 0) {
            $message .= ' Some rows had errors.';
        }

        if (count($import->errors) > 0) {
            session()->flash('import_errors', $import->errors);
        }

        return Toast::to(route('client.orders.index'), $message);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(
            new OrdersTemplateExport(forAdmin: false),
            'aretia-orders-template.xlsx'
        );
    }
}
