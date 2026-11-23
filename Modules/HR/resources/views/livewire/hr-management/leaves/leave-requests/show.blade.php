<div class="container-fluid py-4">
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" @click="show = false"></button>
        </div>
    @endif
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <i class="fas fa-check-circle me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" @click="show = false"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 fw-bold font-hold">تفاصيل طلب الإجازة</h2>
            <p class="text-muted mb-0">عرض تفاصيل وحالة طلب الإجازة</p>
        </div>
        <a href="{{ route('hr.leaves.requests.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
        </a>
    </div>

    @if ($request)
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-gradient-primary text-white py-3">
                        <h5 class="card-title mb-0 fw-bold font-hold">
                            <i class="fas fa-info-circle me-2"></i>معلومات الطلب
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-item p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">
                                        <i class="fas fa-user text-primary me-1"></i>الموظف
                                    </label>
                                    <p class="mb-0 fw-bold font-hold">{{ $request->employee->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">
                                        <i class="fas fa-tag text-primary me-1"></i>نوع الإجازة
                                    </label>
                                    <p class="mb-0 fw-bold font-hold">{{ $request->leaveType->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">
                                        <i class="fas fa-calendar-alt text-success me-1"></i>تاريخ البداية
                                    </label>
                                    <p class="mb-0 fw-bold font-hold">{{ $request->start_date->format('Y-m-d') }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">
                                        <i class="fas fa-calendar-check text-danger me-1"></i>تاريخ النهاية
                                    </label>
                                    <p class="mb-0 fw-bold font-hold">{{ $request->end_date->format('Y-m-d') }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">
                                        <i class="fas fa-clock text-info me-1"></i>المدة
                                    </label>
                                    <p class="mb-0 fw-bold font-hold text-info">{{ number_format($request->duration_days, 1) }} يوم</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="info-item p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">
                                        <i class="fas fa-comment-alt text-warning me-1"></i>سبب الإجازة
                                    </label>
                                    <p class="mb-0 font-hold">{{ $request->reason ?: 'غير محدد' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-info text-white py-3">
                        <h5 class="card-title mb-0 fw-bold font-hold">
                            <i class="fas fa-tasks me-2"></i>حالة الطلب
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <span class="badge fs-4 px-4 py-2 {{ $this->getStatusBadgeClass($request->status) }}">
                                {{ $this->getStatusText($request->status) }}
                            </span>
                            <p class="text-muted mt-3 mb-0">{{ $this->getStatusDescription($request->status) }}</p>
                        </div>

                        @if ($request->approver && $request->approved_at)
                            <div class="alert alert-success border-0 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user-check me-2"></i>
                                    <strong>المعتمد من:</strong>
                                </div>
                                <p class="mb-1 font-hold">{{ $request->approver->name }}</p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>{{ $request->approved_at->format('Y-m-d H:i') }}
                                </small>
                            </div>
                        @endif

                        @if ($request->status === 'rejected' && $request->approver)
                            <div class="alert alert-danger border-0 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user-times me-2"></i>
                                    <strong>مرفوض من:</strong>
                                </div>
                                <p class="mb-1 font-hold">{{ $request->approver->name }}</p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>{{ $request->updated_at->format('Y-m-d H:i') }}
                                </small>
                            </div>
                        @endif

                        @if ($request->status === 'cancelled')
                            <div class="alert alert-warning border-0 mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-ban me-2"></i>
                                    <strong>تاريخ الإلغاء:</strong>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>{{ $request->updated_at->format('Y-m-d H:i') }}
                                </small>
                            </div>
                        @endif

                        <div class="d-grid gap-2">
                            @if ($request->status === 'draft')
                                <button type="button" wire:click="submitRequest" wire:confirm="هل أنت متأكد من تقديم هذا الطلب؟" class="btn btn-main w-100">
                                    <i class="fas fa-paper-plane me-2"></i>تقديم الطلب
                                </button>
                            @endif

                            @if ($request->status === 'submitted' && auth()->user()->can('approve', $request))
                                <button type="button" wire:click="approveRequest" wire:confirm="هل أنت متأكد من الموافقة على هذا الطلب؟" class="btn btn-success w-100">
                                    <i class="fas fa-check me-2"></i>موافقة على الطلب
                                </button>
                                <button type="button" wire:click="rejectRequest" wire:confirm="هل أنت متأكد من رفض هذا الطلب؟" class="btn btn-danger w-100">
                                    <i class="fas fa-times me-2"></i>رفض الطلب
                                </button>
                            @endif

                            @if (in_array($request->status, ['draft', 'submitted']) && auth()->user()->can('update', $request))
                                <button type="button" wire:click="editRequest" wire:confirm="هل أنت متأكد من تعديل هذا الطلب؟" class="btn btn-info w-100">
                                    <i class="fas fa-edit me-2"></i>تعديل الطلب
                                </button>
                            @endif

                            @if (in_array($request->status, ['draft', 'submitted']) && auth()->user()->can('cancel', $request))
                                <button type="button" wire:click="cancelRequest" wire:confirm="هل أنت متأكد من إلغاء هذا الطلب؟" class="btn btn-warning w-100">
                                    <i class="fas fa-ban me-2"></i>إلغاء الطلب
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>لم يتم العثور على الطلب المطلوب.
        </div>
    @endif
</div>
