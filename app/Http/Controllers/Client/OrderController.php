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
        $companyId = auth()->user()->company_id;

        $query = Order::where('company_id', $companyId)
            ->with(['package', 'caseFile'])
            ->latest();

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('subject_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(config('portal.per_page'))->withQueryString();

        $stats = [
            'total' => Order::where('company_id', $companyId)->count(),
            'pending' => Order::where('company_id', $companyId)->where('status', OrderStatus::Pending)->count(),
            'confirmed' => Order::where('company_id', $companyId)->where('status', OrderStatus::Confirmed)->count(),
        ];

        $statusOptions = collect(OrderStatus::cases())->mapWithKeys(
            fn (OrderStatus $s) => [$s->value => ucfirst($s->value)]
        )->all();

        return view('client.orders.index', compact('orders', 'stats', 'statusOptions'));
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
            $rules['custom_request'] = ['required', 'string', 'max:5000'];
        } else {
            $rules += [
                'subject_type' => ['required', 'in:individual,entity'],
                'subject_name' => ['required', 'string', 'max:255'],
                'subject_details' => ['nullable', 'string', 'max:5000'],
                'documents' => ['nullable', 'array', 'max:5'],
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

        return Toast::to(route('client.orders.show', $order), 'Order confirmed successfully.');
    }

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->load(['package', 'documents', 'caseFile.stage', 'caseFile.assignee', 'caseFile.company', 'caseFile.order.user']);

        return view('client.orders.show', compact('order'));
    }

    public function storeDocument(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $this->authorizeOrder($order);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data' => ['required', 'string'],
        ]);

        $this->addDocument($order, auth()->id(), $data['name'], $data['data']);

        return Toast::back('Document uploaded successfully.');
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

    public function downloadDocument(Order $order, OrderDocument $document): BinaryFileResponse
    {
        $this->authorizeOrder($order);
        abort_unless($document->order_id === $order->id, 404);

        return $this->uploads->download($document->path, $document->original_name);
    }

    private function addDocument(Order $order, int $userId, string $name, string $base64): void
    {
        if ($order->documents()->count() >= 5) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data' => 'Maximum 5 documents per order.',
            ]);
        }

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
        abort_unless($order->company_id === auth()->user()->company_id, 403);
    }
}
