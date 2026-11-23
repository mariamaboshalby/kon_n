@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.tasks')
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                @include('components.breadcrumb', [
                    'title' => __('crm::crm.tasks_and_activities'),
                    'breadcrumb_items' => [['label' => __('crm::crm.dashboard'), 'url' => route('admin.dashboard')], ['label' => __('crm::crm.tasks_and_activities')]],
                ])
            </div>
            @can('create Tasks')
                <div>
                    <a href="{{ route('tasks.trashed') }}" class="btn btn-outline-danger me-2">
                        <i class="las la-trash-restore"></i> {{ __('crm::crm.deleted_tasks') }}
                    </a>
                    <a href="{{ route('tasks.timeline') }}" class="btn btn-outline-info me-2">
                        <i class="las la-stream"></i> {{ __('crm::crm.timeline_view') }}
                    </a>
                    <a href="{{ route('tasks.kanban') }}" class="btn btn-outline-primary me-2">
                        <i class="las la-th-large"></i> {{ __('crm::crm.kanban_view') }}
                    </a>
                    <a href="{{ route('tasks.create') }}" class="btn btn-main font-hold fw-bold">
                        <i class="fas fa-plus me-2"></i>
                        {{ __('crm::crm.add_new') }}
                    </a>
                </div>
            @endcan
        </div>
    </div>

    @push('styles')
    <style>
        .task-title-hover {
            color: #0d6efd;
            font-weight: 500;
        }

        .task-title-hover:hover {
            color: #0a58ca;
        }

        .tooltip-inner {
            max-width: 400px;
            text-align: right;
            padding: 10px 15px;
            font-size: 14px;
        }

        /* Status Select Styling */
        .form-select-sm.bg-warning,
        .form-select-sm.bg-info,
        .form-select-sm.bg-success,
        .form-select-sm.bg-danger {
            border: none;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .form-select-sm.bg-warning option,
        .form-select-sm.bg-info option,
        .form-select-sm.bg-success option,
        .form-select-sm.bg-danger option {
            background-color: white;
            color: #212529;
        }
    </style>
    @endpush

    <div class="row">
        <div class="col-lg-12">

            <!-- Filters Section -->
            <div class="card mb-3">
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('tasks.index') }}" id="filterForm">
                        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                            <!-- Date Filter -->
                            <div style="display: flex; flex-direction: column;">
                                <label class="form-label fw-bold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">{{ __('crm::crm.date') }}</label>
                                <select name="date_filter" class="form-select form-select-sm" style="width: 140px; font-size: 0.9rem;">
                                    <option value="today" {{ request('date_filter', 'today') == 'today' ? 'selected' : '' }}>{{ __('crm::crm.today') }}</option>
                                    <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>{{ __('crm::crm.this_week') }}</option>
                                    <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>{{ __('crm::crm.this_month') }}</option>
                                    <option value="overdue" {{ request('date_filter') == 'overdue' ? 'selected' : '' }}>{{ __('crm::crm.overdue') }}</option>
                                    <option value="upcoming" {{ request('date_filter') == 'upcoming' ? 'selected' : '' }}>{{ __('crm::crm.upcoming') }}</option>
                                    <option value="all" {{ request('date_filter') == 'all' ? 'selected' : '' }}>{{ __('crm::crm.all') }}</option>
                                </select>
                            </div>

                            <!-- Task Type Filter -->
                            <div style="display: flex; flex-direction: column;">
                                <label class="form-label fw-bold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">{{ __('crm::crm.type') }}</label>
                                <select name="task_type_id" class="form-select form-select-sm" style="width: 140px; font-size: 0.9rem;">
                                    <option value="">{{ __('crm::crm.all') }}</option>
                                    @foreach($taskTypes as $id => $title)
                                        <option value="{{ $id }}" {{ request('task_type_id') == $id ? 'selected' : '' }}>{{ $title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- User Filter -->
                            <div style="display: flex; flex-direction: column;">
                                <label class="form-label fw-bold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">{{ __('crm::crm.user') }}</label>
                                <select name="user_id" class="form-select form-select-sm" style="width: 140px; font-size: 0.9rem;">
                                    <option value="">{{ __('crm::crm.all') }}</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Priority Filter -->
                            <div style="display: flex; flex-direction: column;">
                                <label class="form-label fw-bold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">{{ __('crm::crm.priority') }}</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.9rem; width: 140px; padding: 0.375rem 0.5rem;">
                                        {{ __('crm::crm.select') }}
                                    </button>
                                    <ul class="dropdown-menu" style="max-height: 200px; overflow-y: auto; min-width: 140px;">
                                        @foreach($priorities as $priority)
                                            <li>
                                                <label class="dropdown-item" style="cursor: pointer; padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                                    <input class="form-check-input me-2" type="checkbox" name="priority[]"
                                                        value="{{ $priority->value }}"
                                                        {{ in_array($priority->value, (array)request('priority', [])) ? 'checked' : '' }}
                                                        onchange="">
                                                    {{ $priority->label() }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div style="display: flex; flex-direction: column;">
                                <label class="form-label fw-bold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">{{ __('crm::crm.status') }}</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.9rem; width: 140px; padding: 0.375rem 0.5rem;">
                                        {{ __('crm::crm.select') }}
                                    </button>
                                    <ul class="dropdown-menu" style="max-height: 200px; overflow-y: auto; min-width: 140px;">
                                        @foreach($statuses as $status)
                                            <li>
                                                <label class="dropdown-item" style="cursor: pointer; padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                                    <input class="form-check-input me-2" type="checkbox" name="status[]"
                                                        value="{{ $status->value }}"
                                                        {{ in_array($status->value, (array)request('status', [])) ? 'checked' : '' }}
                                                        onchange="">
                                                    {{ $status->label() }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <!-- Start Date -->
                            <div style="display: flex; flex-direction: column;">
                                <label class="form-label fw-bold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">{{ __('crm::crm.from') }}</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}" style="width: 140px; font-size: 0.9rem;">
                            </div>

                            <!-- End Date -->
                            <div style="display: flex; flex-direction: column;">
                                <label class="form-label fw-bold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">{{ __('crm::crm.to') }}</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}" style="width: 140px; font-size: 0.9rem;">
                            </div>

                            <!-- Apply Button -->
                            <div style="display: flex; flex-direction: column;">
                                <label style="font-size: 0.9rem; margin-bottom: 0.25rem; visibility: hidden;">{{ __('crm::crm.apply') }}</label>
                                <button type="submit" class="btn btn-primary btn-sm" style="font-size: 0.9rem; padding: 0.375rem 0.6rem; white-space: nowrap;">
                                    <i class="las la-filter"></i> {{ __('crm::crm.apply') }}
                                </button>
                            </div>

                            <!-- Reset Button -->
                            <div style="display: flex; flex-direction: column;">
                                <label style="font-size: 0.9rem; margin-bottom: 0.25rem; visibility: hidden;">{{ __('crm::crm.reset') }}</label>
                                <a href="{{ route('tasks.index') }}" class="btn btn-secondary btn-sm" style="font-size: 0.9rem; padding: 0.375rem 0.6rem; white-space: nowrap;">
                                    <i class="las la-redo"></i> {{ __('crm::crm.reset') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive" style="overflow-x: auto;">

                        <x-table-export-actions table-id="tasks-table" filename="tasks-table" :excel-label="__('crm::crm.export_excel')"
                            :pdf-label="__('crm::crm.export_pdf')" :print-label="__('crm::crm.print')" />

                        <table id="tasks-table" class="table table-striped mb-0" style="min-width: 1200px;">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('crm::crm.client') }}</th>
                                    <th>{{ __('crm::crm.assigned_to') }}</th>
                                    <th>{{ __('crm::crm.created_by') }}</th>
                                    <th>{{ __('crm::crm.task_type') }}</th>
                                    <th>{{ __('crm::crm.task_title') }}</th>
                                    <th>{{ __('crm::crm.priority') }}</th>
                                    <th>{{ __('crm::crm.status') }}</th>
                                    <th>{{ __('crm::crm.start_date') }}</th>
                                    <th>{{ __('crm::crm.end_date') }}</th>
                                    <th>{{ __('crm::crm.duration') }}</th>
                                    <th>{{ __('crm::crm.attachments') }}</th>
                                    @canany(['edit Tasks', 'delete Tasks'])
                                        <th>{{ __('crm::crm.actions') }}</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tasks as $task)
                                    <tr class="text-center">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ optional($task->client)->cname }}</td>
                                        <td>{{ optional($task->user)->name }}</td>
                                        <td>{{ optional($task->creator)->name }}</td>
                                        <td>{{ $task->taskType->title }}</td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-link p-0 task-title-hover"
                                                    style="text-decoration: underline dotted; font-size: inherit;"
                                                    @click="showTaskModal({{ $task->id }})">
                                                {{ $task->title }}
                                            </button>
                                        </td>
                                        <td>
                                            @php
                                                $priority = is_string($task->priority)
                                                    ? \Modules\CRM\Enums\TaskPriorityEnum::tryFrom($task->priority)
                                                    : $task->priority;
                                            @endphp

                                            @if ($priority)
                                                <span class="badge bg-{{ $priority->color() }}">
                                                    {{ $priority->label() }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $status = is_string($task->status)
                                                    ? \Modules\CRM\Enums\TaskStatusEnum::tryFrom($task->status)
                                                    : $task->status;
                                            @endphp

                                            @can('edit Tasks')
                                                <div x-data="{
                                                    status: '{{ $task->status }}',
                                                    updating: false,
                                                    updateStatus(taskId, newStatus) {
                                                        this.updating = true;
                                                        fetch('{{ route('tasks.updateStatus') }}', {
                                                            method: 'POST',
                                                            headers: {
                                                                'Content-Type': 'application/json',
                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                            },
                                                            body: JSON.stringify({
                                                                task_id: taskId,
                                                                status: newStatus
                                                            })
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success) {
                                                                this.status = newStatus;
                                                                const alertDiv = document.createElement('div');
                                                                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-40 translate-middle-x mt-3';
                                                                alertDiv.style.zIndex = '9999';
                                                                alertDiv.style.minWidth = '350px';
                                                                alertDiv.style.fontSize = '1.1rem';
                                                                alertDiv.style.padding = '1rem 1.5rem';
                                                                alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                                                                alertDiv.innerHTML = '<i class=&quot;las la-check-circle me-2&quot; style=&quot;font-size: 1.3rem;&quot;></i>' + data.message + '<button type=&quot;button&quot; class=&quot;btn-close&quot; data-bs-dismiss=&quot;alert&quot;></button>';
                                                                document.body.appendChild(alertDiv);
                                                                setTimeout(() => alertDiv.remove(), 3000);
                                                            }
                                                        })
                                                        .catch(error => {
                                                            console.error('Error:', error);
                                                            window.alert('{{ __('crm::crm.an_error_occurred_while_updating_status') }}');
                                                        })
                                                        .finally(() => {
                                                            this.updating = false;
                                                        });
                                                    }
                                                }">
                                                    <select
                                                        class="form-select form-select-sm " style='width:90px; margin:auto'
                                                        :class="'bg-' + {
                                                            '{{ \Modules\CRM\Enums\TaskStatusEnum::PENDING->value }}': 'warning',
                                                            '{{ \Modules\CRM\Enums\TaskStatusEnum::IN_PROGRESS->value }}': 'info',
                                                            '{{ \Modules\CRM\Enums\TaskStatusEnum::COMPLETED->value }}': 'success',
                                                            '{{ \Modules\CRM\Enums\TaskStatusEnum::CANCELLED->value }}': 'danger'
                                                        }[status] + ' text-white'"
                                                        x-model="status"
                                                        @change="updateStatus({{ $task->id }}, $event.target.value)"
                                                        :disabled="updating"
                                                        style="min-width: 140px; cursor: pointer;">
                                                        @foreach($statuses as $statusOption)
                                                            <option value="{{ $statusOption->value }}">
                                                                {{ $statusOption->label() }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div x-show="updating" class="spinner-border spinner-border-sm mt-1" role="status">
                                                        <span class="visually-hidden">{{ __('crm::crm.loading') }}...</span>
                                                    </div>
                                                </div>
                                            @else
                                                @if ($status)
                                                    <span class="badge bg-{{ $status->color() }}">
                                                        {{ $status->label() }}
                                                    </span>
                                                @endif
                                            @endcan
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($task->start_date)->format('Y-m-d') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') }}</td>
                                        <td>
                                            @if($task->duration)
                                                <span class="badge bg-info">{{ $task->formatted_duration }} {{ __('crm::crm.hours') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($task->hasMedia('tasks'))
                                                @php
                                                    $mediaCount = $task->getMedia('tasks')->count();
                                                @endphp
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#attachmentModal{{ $task->id }}">
                                                    <i class="fas fa-paperclip"></i>
                                                    {{ __('crm::crm.view') }} ({{ $mediaCount }})
                                                </button>

                                                <div class="modal fade" id="attachmentModal{{ $task->id }}"
                                                    tabindex="-1" aria-labelledby="attachmentModalLabel"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-xl">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="attachmentModalLabel">
                                                                    {{ __('crm::crm.task_attachments') }} ({{ $mediaCount }} {{ __('crm::crm.files') }})
                                                                </h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                @if($task->description)
                                                                <div class="mb-4">
                                                                    <h6 class="fw-bold">{{ __('crm::crm.description') }}:</h6>
                                                                    <div class="p-3 bg-light rounded">
                                                                        {{ $task->description }}
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                @endif

                                                                <h6 class="fw-bold mb-3">{{ __('crm::crm.attachments') }}:</h6>

                                                                <div class="row g-3">
                                                                    @foreach($task->getMedia('tasks') as $media)
                                                                        <div class="col-md-6">
                                                                            <div class="card h-100">
                                                                                <div class="card-body">
                                                                                    @php
                                                                                        $imageUrl = $media->getUrl();
                                                                                        $isImage = Str::contains($media->mime_type, 'image');
                                                                                    @endphp

                                                                                    @if ($isImage)
                                                                                        <div class="text-center mb-3">
                                                                                            <img src="{{ $imageUrl }}"
                                                                                                class="img-fluid rounded shadow-sm"
                                                                                                style="max-height: 300px; object-fit: contain; border: 2px solid #dee2e6;"
                                                                                                alt="{{ $media->file_name }}">
                                                                                        </div>
                                                                                    @else
                                                                                        <div class="text-center mb-3">
                                                                                            <i class="las la-file-alt" style="font-size: 4rem; color: #6c757d;"></i>
                                                                                        </div>
                                                                                    @endif

                                                                                    <p class="text-muted small mb-2">
                                                                                        <i class="las la-file"></i> {{ $media->file_name }}
                                                                                    </p>
                                                                                    <p class="text-muted small mb-3">
                                                                                        <i class="las la-hdd"></i> {{ $media->human_readable_size }}
                                                                                    </p>

                                                                                    <div class="d-flex gap-2">
                                                                                        <a href="{{ $imageUrl }}"
                                                                                           class="btn btn-sm btn-outline-primary flex-fill"
                                                                                           target="_blank">
                                                                                            <i class="las la-external-link-alt"></i> {{ __('crm::crm.open') }}
                                                                                        </a>
                                                                                        <a href="{{ $imageUrl }}"
                                                                                           class="btn btn-sm btn-outline-success flex-fill"
                                                                                           download>
                                                                                            <i class="las la-download"></i> {{ __('crm::crm.download') }}
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">{{ __('crm::crm.close') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">{{ __('crm::crm.no_attachment') }}</span>
                                            @endif
                                        </td>
                                        @canany(['edit Tasks', 'delete Tasks'])
                                            <td>
                                                @can('view Tasks')
                                                    <a class="btn btn-primary btn-icon-square-sm"
                                                        href="{{ route('tasks.show', $task->id) }}"
                                                        title="{{ __('crm::crm.view') }}">
                                                        <i class="las la-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('edit Tasks')
                                                    <a class="btn btn-success btn-icon-square-sm"
                                                        href="{{ route('tasks.edit', $task->id) }}"
                                                        title="{{ __('crm::crm.edit') }}">
                                                        <i class="las la-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete Tasks')
                                                    <form action="{{ route('tasks.destroy', $task->id) }}" method="POST"
                                                        style="display:inline-block;"
                                                        onsubmit="return confirm('{{ __('crm::crm.confirm_delete_task') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-icon-square-sm"
                                                            title="{{ __('crm::crm.delete') }}">
                                                            <i class="las la-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        @endcanany
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center">
                                            <div class="alert alert-info py-3 mb-0"
                                                style="font-size: 1.2rem; font-weight: 500;">
                                                <i class="las la-info-circle me-2"></i>
                                                {{ __('crm::crm.no_tasks_added_yet') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $tasks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Task Details Modal --}}
    <div x-data="taskModal()" x-init="init()">
        <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" x-text="task.title ?? '...'"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        {{-- Loading --}}
                        <div x-show="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('crm::crm.loading') }}</span>
                            </div>
                        </div>

                        <div x-show="!loading && task.id">

                            {{-- Info Grid --}}
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.client') }}</label>
                                    <div x-text="task.client ?? '-'"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.assigned_to') }}</label>
                                    <div x-text="task.user ?? '-'"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.created_by') }}</label>
                                    <div x-text="task.creator ?? '-'"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.task_type') }}</label>
                                    <div x-text="task.task_type ?? '-'"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.priority') }}</label>
                                    <div>
                                        <span class="badge" :class="'bg-' + (task.priority_color ?? 'secondary')" x-text="task.priority_label ?? '-'"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.status') }}</label>
                                    <div>
                                        <span class="badge" :class="'bg-' + (task.status_color ?? 'secondary')" x-text="task.status_label ?? '-'"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.start_date') }}</label>
                                    <div x-text="task.start_date ?? '-'"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.end_date') }}</label>
                                    <div x-text="task.due_date ?? '-'"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.duration') }}</label>
                                    <div>
                                        <template x-if="task.duration">
                                            <span class="badge bg-info" x-text="task.duration + ' {{ __('crm::crm.hours') }}'"></span>
                                        </template>
                                        <template x-if="!task.duration">
                                            <span class="text-muted">-</span>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.created_at') }}</label>
                                    <div x-text="task.created_at ?? '-'"></div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-muted small">{{ __('crm::crm.description') }}</label>
                                    <div class="p-3 bg-light rounded" x-text="task.description || '{{ __('crm::crm.no_description') }}'"></div>
                                </div>
                                <template x-if="task.client_comment">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">{{ __('crm::crm.client_comment') }}</label>
                                        <div x-text="task.client_comment"></div>
                                    </div>
                                </template>
                                <template x-if="task.user_comment">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">{{ __('crm::crm.user_comment') }}</label>
                                        <div x-text="task.user_comment"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- Attachments --}}
                            <template x-if="task.media && task.media.length > 0">
                                <div class="mb-4">
                                    <h6 class="fw-bold border-bottom pb-2 mb-3">
                                        <i class="las la-paperclip"></i> {{ __('crm::crm.attachments') }}
                                    </h6>
                                    <div class="row g-3">
                                        <template x-for="file in task.media" :key="file.url">
                                            <div class="col-md-4">
                                                <div class="card h-100">
                                                    <div class="card-body text-center">
                                                        <template x-if="file.is_image">
                                                            <img :src="file.url" class="img-fluid rounded mb-2" style="max-height:180px; object-fit:contain;">
                                                        </template>
                                                        <template x-if="!file.is_image">
                                                            <i class="las la-file-alt mb-2" style="font-size:3rem; color:#6c757d;"></i>
                                                        </template>
                                                        <p class="small text-muted mb-1" x-text="file.name"></p>
                                                        <p class="small text-muted mb-2" x-text="file.size"></p>
                                                        <div class="d-flex gap-2 justify-content-center">
                                                            <a :href="file.url" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="las la-external-link-alt"></i>
                                                            </a>
                                                            <a :href="file.url" download class="btn btn-sm btn-outline-success">
                                                                <i class="las la-download"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Activity Log --}}
                            <template x-if="task.activity_logs && task.activity_logs.length > 0">
                                <div>
                                    <h6 class="fw-bold border-bottom pb-2 mb-3">
                                        <i class="las la-history"></i> {{ __('crm::crm.activity_log') }}
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>{{ __('crm::crm.activity_event') }}</th>
                                                    <th>{{ __('crm::crm.activity_field') }}</th>
                                                    <th>{{ __('crm::crm.activity_old_value') }}</th>
                                                    <th>{{ __('crm::crm.activity_new_value') }}</th>
                                                    <th>{{ __('crm::crm.activity_by') }}</th>
                                                    <th>{{ __('crm::crm.activity_at') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="log in task.activity_logs" :key="log.created_at + log.event">
                                                    <tr>
                                                        <td>
                                                            <span class="badge"
                                                                :class="log.event === 'created' ? 'bg-success' : (log.event === 'status_changed' ? 'bg-warning text-dark' : 'bg-info')"
                                                                x-text="log.event"></span>
                                                        </td>
                                                        <td><span class="text-muted small" x-text="log.field ?? '—'"></span></td>
                                                        <td><span class="text-danger small" x-text="log.old_value ?? '—'"></span></td>
                                                        <td><span class="text-success small" x-text="log.new_value ?? '—'"></span></td>
                                                        <td x-text="log.user ?? '—'"></td>
                                                        <td><small class="text-muted" x-text="log.created_at"></small></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </div>
                    <div class="modal-footer">
                        @can('edit Tasks')
                            <a :href="task.edit_url" class="btn btn-primary" x-show="task.edit_url">
                                <i class="las la-edit"></i> {{ __('crm::crm.edit') }}
                            </a>
                        @endcan
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('crm::crm.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function taskModal() {
        return {
            task: {},
            loading: false,
            modalInstance: null,

            init() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
                window.showTaskModal = (id) => this.open(id);
            },

            open(id) {
                this.task = {};
                this.loading = true;
                this.modalInstance.show();

                fetch(`/crm/tasks/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => { this.task = data.task; })
                .catch(() => { this.task = { title: '{{ __('crm::crm.error') }}' }; })
                .finally(() => { this.loading = false; });
            }
        };
    }

    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                html: true,
                trigger: 'hover'
            });
        });
    });
</script>
@endpush
