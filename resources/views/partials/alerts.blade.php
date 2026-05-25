@php
    $toasts = [];
    if ($t = session('toast')) {
        $toasts[] = is_array($t) ? $t : ['type' => 'success', 'title' => 'Success', 'message' => $t];
    }
    if (session('success')) {
        $toasts[] = ['type' => 'success', 'title' => 'Success', 'message' => session('success'), 'duration' => 5000];
    }
    if (session('info')) {
        $toasts[] = ['type' => 'info', 'title' => 'Info', 'message' => session('info'), 'duration' => 5000];
    }
    if (isset($errors) && $errors->any()) {
        $toasts[] = [
            'type' => 'error',
            'title' => 'Could not save',
            'message' => $errors->first(),
            'duration' => 5000,
        ];
    }
    if (session('import_errors') && count(session('import_errors')) > 0) {
        $count = count(session('import_errors'));
        $first = session('import_errors')[0];
        $toasts[] = [
            'type' => 'error',
            'title' => 'Import issues',
            'message' => "{$count} row(s) failed. Row {$first['row']}: {$first['message']}",
            'duration' => 5000,
        ];
    }
@endphp
@if(count($toasts) > 0)
<script>window.__toasts = @json($toasts);</script>
@endif
