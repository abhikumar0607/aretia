@php
    $dueDateAction = $dueDateAction ?? null;
    $dueDateValue = old('due_date', $order->due_date?->format('Y-m-d'));
@endphp
@if($dueDateAction)
<section class="card order-due-date-card">
    <div class="case-panel-head">
        <h3>Due date</h3>
        @if($order->due_date)
            <span class="pill pill-package">{{ $order->due_date->format('d M Y') }}</span>
        @else
            <span class="pill pill-muted">Not set</span>
        @endif
    </div>
    <form method="POST" action="{{ $dueDateAction }}" class="order-due-date-form">
        @csrf
        @method('PATCH')
        <div class="form-field">
            <label for="order-due-date">{{ $order->due_date ? 'Update due date' : 'Set due date' }}</label>
            <input type="date" id="order-due-date" name="due_date" value="{{ $dueDateValue }}" min="{{ now()->format('Y-m-d') }}">
            <p class="form-field-hint">Optional. Clients and the assigned analyst are notified by email and in the portal when a due date is saved.</p>
        </div>
        <div class="order-due-date-actions">
            <button type="submit" class="btn btn-primary btn-sm">Save due date</button>
            @if($order->due_date)
                <button type="submit" name="clear_due_date" value="1" class="btn btn-secondary btn-sm" formnovalidate>Clear due date</button>
            @endif
        </div>
    </form>
</section>
@endif
