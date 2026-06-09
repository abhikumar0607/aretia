<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\ServicePackage;
use App\Services\OrderCreationService;
use App\Services\OrderDueDateService;
use App\Services\PublicUploadService;
use App\Support\CompanyFilter;
use App\Support\OrderDuplicateSubjects;
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
        private OrderCreationService $orderService,
        private OrderDueDateService $dueDates,
        private PublicUploadService $uploads,
    ) {}

    public function index(Request $request): View
    {
        $companyIds = CompanyFilter::scopedCompanyIdsForUser($request->user());

        $query = Order::whereIn('company_id', $companyIds)
            ->with(['company', 'package', 'caseFile'])
            ->latest();

        OrderListFilters::apply($query, $request);

        $orders = $query->paginate(config('portal.per_page'))->withQueryString();

        OrderDuplicateSubjects::markOnCollection($orders, $companyIds);

        $stats = [
            'total' => Order::whereIn('company_id', $companyIds)->count(),
            'pending' => Order::whereIn('company_id', $companyIds)->where('status', OrderStatus::Pending)->count(),
            'confirmed' => Order::whereIn('company_id', $companyIds)->where('status', OrderStatus::Confirmed)->count(),
        ];

        $statusOptions = OrderListFilters::statusOptions();
        $companyOptions = CompanyFilter::optionsForUser($request->user());

        return view('client.orders.index', compact('orders', 'stats', 'statusOptions', 'companyOptions'));
    }

    public function create(Request $request): View
    {
        $packages = ServicePackage::where('is_active', true)->orderBy('sort_order')->get();
        $selected = $request->query('package')
            ? ServicePackage::where('slug', $request->query('package'))->first()
            : null;

        return view('client.orders.create', compact('packages', 'selected'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $package = ServicePackage::findOrFail($request->input('service_package_id'));

        $rules = [
            'service_package_id' => ['required', 'exists:service_packages,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
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
        $user = auth()->user();

        $order = $this->orderService->createFromRow([
            'package_slug' => $package->slug,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_name' => $data['subject_name'] ?? null,
            'subject_details' => $data['subject_details'] ?? null,
            'custom_request' => $data['custom_request'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ], $user, false);

        if (! empty($data['documents'] ?? null)) {
            foreach ($data['documents'] as $doc) {
                $this->addDocument($order, $user->id, $doc['name'], $doc['data']);
            }
        }

        return Toast::to(route('client.orders.show', $order), 'Order submitted for approval. We will notify you once it is confirmed.');
    }

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->load(['package', 'documents.uploader', 'caseFile.stage', 'caseFile.assignee', 'caseFile.company', 'caseFile.order.user']);

        return view('client.orders.show', compact('order'));
    }

    public function storeDocument(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $this->authorizeOrder($order);

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
                $this->addDocument($order, auth()->id(), $name, $base64);
                continue;
            }
            $this->addDocument($order, auth()->id(), $name, $base64);
        }

        return Toast::back('Document(s) uploaded successfully.');
    }

    public function updateDueDate(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($request->boolean('clear_due_date')) {
            $this->dueDates->apply($order, null, $request->user(), true);

            return Toast::back('Due date cleared.');
        }

        $data = $request->validate([
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $hadDueDate = $order->due_date !== null;

        $this->dueDates->apply(
            $order,
            $this->dueDates->parseOptional($data['due_date']),
            $request->user(),
            $hadDueDate
        );

        return Toast::back('Due date saved. Team members have been notified.');
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

    private function authorizeOrder(Order $order): void
    {
        CompanyFilter::authorizeCompanyAccess($order->company_id);
    }

    private function authorizeOrderDocument(Order $order, OrderDocument $document): void
    {
        $this->authorizeOrder($order);
        abort_unless($document->order_id === $order->id, 404);
        abort_unless($document->isVisibleToClient(), 403);
    }
}
