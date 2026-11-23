{{-- Print Preview Modal --}}
<div class="modal fade" id="printPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 12px 40px rgba(0,0,0,.18);overflow:hidden;">

            <div class="modal-header py-3 px-4" style="background:var(--rpos-dark);border:none;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;background:#10b981;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-print text-white" style="font-size:.85rem;"></i>
                    </div>
                    <h6 class="modal-title fw-bold mb-0 text-white">{{ __('pos.print_preview') }}</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0" style="background:#e5e7eb;">
                <iframe id="printPreviewFrame"
                        style="width:100%;height:500px;border:none;display:block;"
                        sandbox="allow-same-origin allow-scripts"></iframe>
            </div>

            <div class="modal-footer py-2 px-4 gap-2" style="border-top:1px solid var(--rpos-border);background:#fff;">
                <button type="button" class="btn btn-sm btn-light fw-bold border" data-bs-dismiss="modal" style="border-radius:8px;">
                    <i class="fas fa-times me-1"></i>{{ __('pos.close') }}
                </button>
                <button type="button" class="btn btn-sm btn-success fw-bold" id="printPreviewBtn" style="border-radius:8px;">
                    <i class="fas fa-print me-1"></i>{{ __('pos.print') }}
                </button>
            </div>

        </div>
    </div>
</div>
