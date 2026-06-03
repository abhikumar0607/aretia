<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\OrdersTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\OrdersImport;
use App\Services\OrderCreationService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkOrderController extends Controller
{
    public function show(): View
    {
        $packages = \App\Models\ServicePackage::where('is_active', true)->orderBy('sort_order')->get();

        return view('superadmin.orders.import', compact('packages'));
    }

    public function import(Request $request, OrderCreationService $orderService): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'],
        ]);

        $import = new OrdersImport(auth()->user(), true, $orderService);
        Excel::import($import, $request->file('file'));

        $message = "{$import->imported} order(s) created. Cases opened and clients notified.";
        if (count($import->errors) > 0) {
            $message .= ' Some rows had errors.';
        }

        if (count($import->errors) > 0) {
            session()->flash('import_errors', $import->errors);
        }

        return Toast::to(route('superadmin.orders.index'), $message);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(
            new OrdersTemplateExport(forAdmin: true),
            'aretia-orders-template-superadmin.xlsx'
        );
    }
}

