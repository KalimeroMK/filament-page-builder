<div style="display: flex; flex-direction: column; gap: 12px;">
    <div>
        <strong>Submitted:</strong> {{ $record->created_at?->toDateTimeString() }}
        &nbsp;|&nbsp; <strong>Page/Popup ID:</strong> {{ $record->page_id }}
        &nbsp;|&nbsp; <strong>Element:</strong> {{ $record->element_id }}
    </div>
    <div>
        <strong>Data:</strong>
        <table style="width: 100%; margin-top: 8px; border-collapse: collapse; font-size: 13px;">
            @foreach(($record->data ?? []) as $key => $value)
                <tr>
                    <td style="border: 1px solid #e2e8f0; padding: 6px 10px; font-weight: 600; width: 30%;">{{ $key }}</td>
                    <td style="border: 1px solid #e2e8f0; padding: 6px 10px;">{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
    @if(!empty($record->actions_log))
        <div>
            <strong>Actions Log:</strong>
            <pre style="background: #f8fafc; padding: 10px; border-radius: 6px; font-size: 12px; overflow-x: auto;">{{ json_encode($record->actions_log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
