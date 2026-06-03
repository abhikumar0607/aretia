<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\ServicePackage;
use App\Services\OrderApprovalService;
use App\Services\OrderCreationService;
use App\Services\OrderDueDateService;
use App\Services\PublicUploadService;
use App\Support\CompanyFilter;
use App\Support\OrderListFilters;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function __construct(
        private OrderDueDateService $dueDates,
        private OrderApprovalService $approval,
        private OrderCreationService $orderService,
        private PublicUploadService $uploads,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        $orders = Order::query()
            ->with(['company', 'package', 'user', 'caseFile'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('subject_name', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->tap(fn ($query) => OrderListFilters::apply($query, $request))
            ->latest()
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        $stats = [
            'pending' => Order::where('status', OrderStatus::Pending)->count(),
            'confirmed' => Order::where('status', OrderStatus::Confirmed)->count(),
        ];

        $statusOptions = OrderListFilters::statusOptions();
        $companyOptions = CompanyFilter::optionsForUser($request->user());

        return view('admin.orders.index', compact('orders', 'stats', 'search', 'statusOptions', 'companyOptions'));
    }

    public function create(Request $request): View
    {
        $packages = ServicePackage::where('is_active', true)->orderBy('sort_order')->get();
        $companies = Company::where('status', CompanyStatus::Active)->orderBy('name')->get();
        $selected = $request->query('package')
            ? ServicePackage::where('slug', $request->query('package'))->first()
            : null;

        return view('admin.orders.create', compact('packages', 'companies', 'selected'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $company = Company::where('status', CompanyStatus::Active)
            ->findOrFail($request->input('company_id'));

        $package = ServicePackage::findOrFail($request->input('service_package_id'));

        $rules = [
            'company_id' => ['required', 'exists:companies,id'],
            'service_package_id' => ['required', 'exists:service_packages,id'],
            'due_date' => ['nullable', 'date'],
        ];

        if ($package->is_custom) {
            $rules += [
                'custom_request' => ['required', 'string', 'max:5000'],
                'subject_type' => ['required', 'in:individual,entity'],
                'subject_name' => ['nullable', 'string', 'max:255'],
                'subject_details' => ['nullable', 'string', 'max:5000'],
                'documents' => ['nullable', 'array'],
                'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
                'documents.*.data' => ['required_with:documents', 'string'],
            ];
        } else {
            $rules += [
                'subject_type' => ['required', 'in:individual,entity'],
                'subject_name' => ['required', 'string', 'max:255'],
                'subject_details' => ['nullable', 'string', 'max:5000'],
                'documents' => ['nullable', 'array'],
                'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
                'documents.*.data' => ['required_with:documents', 'string'],
            ];
        }

        $data = $request->validate($rules);

        $order = $this->orderService->createFromRow([
            'company_email' => $company->email,
            'package_slug' => $package->slug,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_name' => $data['subject_name'] ?? null,
            'subject_details' => $data['subject_details'] ?? null,
            'custom_request' => $data['custom_request'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ], $request->user(), true);

        if (! empty($data['documents'] ?? null)) {
            foreach ($data['documents'] as $doc) {
                $this->addDocument($order, $request->user()->id, $doc['name'], $doc['data']);
            }
        }

        $order->load('caseFile');

        $message = 'Order created. Case opened and client notified.';
        if ($order->caseFile) {
            return Toast::to(route('admin.cases.show', $order->caseFile), $message);
        }

        return Toast::to(route('admin.orders.show', $order), $message);
    }

    public function show(Order $order): View
    {
        $order->load(['company', 'package', 'user', 'documents', 'caseFile.stage', 'caseFile.assignee', 'caseFile.analysts']);

        return view('admin.orders.show', compact('order'));
    }

    public function storeDocument(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'string'],
            'documents' => ['nullable', 'array', 'min:1'],
            'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.data' => ['required_with:documents', 'string'],
        ]);

        $docs = $data['documents'] ?? null;
        if (! $docs) {
            if (empty($data['name']) || empty($data['data'])) {
                return Toast::back('Please select at least one file.');
            }
            $docs = [['name' => $data['name'], 'data' => $data['data']]];
        }

        foreach ($docs as $doc) {
            $name = $doc['name'];
            $base64 = $doc['data'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === 'zip') {
                $this->addDocument($order, $request->user()->id, $name, $base64);
                continue;
            }
            $this->addDocument($order, $request->user()->id, $name, $base64);
        }

        return Toast::back('Document(s) uploaded successfully.');
    }

    public function approve(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $case = $this->approval->approve($order, $request->user());

        return Toast::to(
            route('admin.cases.show', $case),
            'Order approved. Case '.$case->reference.' created and client notified.'
        );
    }

    public function reject(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->approval->reject($order, $request->user(), $data['rejection_reason']);

        return Toast::to(route('admin.orders.index'), 'Order rejected. Client has been notified.');
    }

    public function previewDocument(Order $order, OrderDocument $document): BinaryFileResponse
    {
        $this->authorizeOrderDocument($order, $document);

        $full = $this->uploads->absolutePath($document->path);
        abort_unless(is_file($full), 404);

        return response()->file($full);
    }

    public function downloadDocument(Order $order, OrderDocument $document): BinaryFileResponse
    {
        $this->authorizeOrderDocument($order, $document);

        return $this->uploads->download($document->path, $document->original_name);
    }

    public function updateDueDate(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        if ($request->boolean('clear_due_date')) {
            $this->dueDates->apply($order, null, $request->user(), true);

            return Toast::back('Due date cleared.');
        }

        $data = $request->validate([
            'due_date' => ['required', 'date'],
        ]);

        $hadDueDate = $order->due_date !== null;

        $this->dueDates->apply(
            $order,
            $this->dueDates->parseOptional($data['due_date']),
            $request->user(),
            $hadDueDate
        );

        return Toast::back('Due date saved. Client and analyst notified.');
    }

    private function addDocument(Order $order, int $userId, string $name, string $base64): void
    {
        $binary = $this->uploads->decodeBase64($base64);
        $path = $this->uploads->storeBinary($binary, $name, 'orders', $order->id);

        OrderDocument::create([
            'order_id' => $order->id,
            'uploaded_by' => $userId,
            'original_name' => $name,
            'path' => $path,
        ]);
    }

    private function authorizeOrderDocument(Order $order, OrderDocument $document): void
    {
        abort_unless($document->order_id === $order->id, 404);
    }
}
