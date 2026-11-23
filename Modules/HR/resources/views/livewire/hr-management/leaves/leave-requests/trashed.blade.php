<div class="container-fluid">
    <div class="row">
        <!-- رسائل النجاح -->
        @if (session()->has('message'))
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                    <i class="fas fa-check-circle"></i>
                    {{ session('message') }}
                    <button type="button" class="btn-close" @click="show = false" aria-label="Close"></button>
                </div>
            </div>
        @endif

        <!-- رسائل الخطأ -->
        @if (session()->has('error'))
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" @click="show = false" aria-label="Close"></button>
                </div>
            </div>
        @endif

        <!-- رسائل Livewire -->
        <div class="col-12" x-data="{ showMessage: false, message: '', messageType: 'success' }" x-show="showMessage">
            <div class="alert alert-dismissible fade show" 
                 :class="messageType === 'success' ? 'alert-success' : 'alert-danger'"
                 x-show="showMessage" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                <i class="fas" :class="messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
                <span x-text="message"></span>
                <button type="button" class="btn-close" @click="showMessage = false" aria-label="Close"></button>
            </div>
        </div>

        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('show-message', (data) => {
                    const alertDiv = document.querySelector('[x-data*="showMessage"]');
                    if (alertDiv) {
                        const alpine = Alpine.$data(alertDiv);
                        alpine.message = data.message;
                        alpine.messageType = data.type;
                        alpine.showMessage = false;
                        setTimeout(() => {
                            alpine.showMessage = true;
                            setTimeout(() => {
                                alpine.showMessage = false;
                            }, 5000);
                        }, 100);
                    }
                });
            });
        </script>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الطلبات المحذوفة</h3>
                </div>
                <div class="card-body">
                    <!-- فلاتر البحث -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">البحث</label>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                                placeholder="البحث في اسم الموظف...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الموظف</label>
                            <select wire:model.live="selectedEmployee" class="form-select font-hold fw-bold font-14">
                                <option value="">جميع الموظفين</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">نوع الإجازة</label>
                            <select wire:model.live="selectedLeaveType" class="form-select font-hold fw-bold font-14">
                                <option value="">جميع الأنواع</option>
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الحالة</label>
                            <select wire:model.live="selectedStatus" class="form-select font-hold fw-bold font-14">
                                <option value="">جميع الحالات</option>
                                <option value="draft">مسودة</option>
                                <option value="submitted">مقدم</option>
                                <option value="approved">معتمد</option>
                                <option value="rejected">مرفوض</option>
                                <option value="cancelled">ملغي</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">من تاريخ</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control font-hold fw-bold font-14">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date" wire:model.live="dateTo" class="form-control font-hold fw-bold font-14">
                        </div>
                    </div>

                    <!-- أزرار الإجراءات -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <a href="{{ route('hr.leaves.requests.index') }}" class="btn btn-secondary font-hold fw-bold">
                                <i class="fas fa-arrow-right"></i>
                                العودة للطلبات
                            </a>
                        </div>
                    </div>

                    <!-- جدول الطلبات المحذوفة -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead style="background-color: #4a5568;">
                                <tr>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">الموظف</th>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">نوع الإجازة</th>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">تاريخ البداية</th>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">تاريخ النهاية</th>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">المدة (أيام)</th>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">الحالة</th>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">تاريخ الحذف</th>
                                    <th style="color: white !important;" class="font-hold fw-bold font-14">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                    <tr>
                                        <td class="font-hold fw-bold font-14">{{ $request->employee->name }}</td>
                                        <td class="font-hold fw-bold font-14">{{ $request->leaveType->name }}</td>
                                        <td class="font-hold fw-bold font-14">{{ $request->start_date->format('Y-m-d') }}</td>
                                        <td class="font-hold fw-bold font-14">{{ $request->end_date->format('Y-m-d') }}</td>
                                        <td class="font-hold fw-bold font-14">{{ number_format($request->duration_days, 1) }}</td>
                                        <td>
                                            <span class="badge font-hold fw-bold font-14 {{ $this->getStatusBadgeClass($request->status) }}">
                                                {{ $this->getStatusText($request->status) }}
                                            </span>
                                        </td>
                                        <td class="font-hold fw-bold font-14">{{ $request->deleted_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-group d-flex justify-content-center align-items-center gap-2" role="group">
                                                <button type="button"
                                                    wire:click="restoreRequest({{ $request->id }})"
                                                    wire:confirm="هل أنت متأكد من استرجاع هذا الطلب؟"
                                                    class="btn btn-sm btn-success font-hold fw-bold font-14">
                                                    <i class="fas fa-undo" title="استرجاع الطلب"></i>
                                                </button>
                                                <button type="button"
                                                    wire:click="forceDeleteRequest({{ $request->id }})"
                                                    wire:confirm="هل أنت متأكد من حذف هذا الطلب نهائياً؟ لا يمكن التراجع عن هذا الإجراء!"
                                                    class="btn btn-sm btn-danger font-hold fw-bold font-14">
                                                    <i class="fas fa-trash-alt" title="حذف نهائي"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center font-hold fw-bold font-14">لا توجد طلبات محذوفة</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- ترقيم الصفحات -->
                    <div class="d-flex justify-content-center">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
