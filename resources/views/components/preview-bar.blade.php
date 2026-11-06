<div class="fpb-preview-toolbar" style="position: sticky; top: 0; z-index: 9999; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; background: #0f172a; color: #f8fafc; font-size: 13px; border-bottom: 1px solid #334155;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="background: #f59e0b; color: #111827; font-weight: 700; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; font-size: 11px;">
            Preview Mode (Draft)
        </span>
        <strong>{{ $page->title }}</strong>
        <span style="color: #94a3b8;">({{ $page->status->getLabel() }})</span>
        @if($page->hasUnpublishedChanges())
            <span style="color: #fbbf24;">• Has unpublished changes</span>
        @else
            <span style="color: #34d399;">• Synced with Published</span>
        @endif
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" onclick="document.getElementById('fpb-viewport-frame').style.maxWidth='100%'; document.getElementById('fpb-viewport-frame').style.margin='0 auto';" style="background: #1e293b; color: #f8fafc; border: 1px solid #475569; padding: 4px 10px; border-radius: 4px; cursor: pointer;">
            Desktop
        </button>
        <button type="button" onclick="document.getElementById('fpb-viewport-frame').style.maxWidth='1024px'; document.getElementById('fpb-viewport-frame').style.margin='0 auto';" style="background: #1e293b; color: #f8fafc; border: 1px solid #475569; padding: 4px 10px; border-radius: 4px; cursor: pointer;">
            Tablet (1024px)
        </button>
        <button type="button" onclick="document.getElementById('fpb-viewport-frame').style.maxWidth='375px'; document.getElementById('fpb-viewport-frame').style.margin='0 auto';" style="background: #1e293b; color: #f8fafc; border: 1px solid #475569; padding: 4px 10px; border-radius: 4px; cursor: pointer;">
            Mobile (375px)
        </button>
    </div>
</div>
