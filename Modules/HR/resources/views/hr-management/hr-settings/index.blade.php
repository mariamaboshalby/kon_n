@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.hr_settings'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.hr_settings')],
        ],
    ])

    <div class="container-fluid" style="font-family: var(--masar-font-family); direction: rtl;">
        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ─── الإعدادات العامة ─── --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-hold fw-bold mb-0">{{ __('hr::hr.hr_settings') }}</h3>
                @can('edit HR Settings')
                    <a href="{{ route('hr.settings.edit') }}" class="btn btn-main font-hold fw-bold">
                        <i class="fas fa-edit"></i> {{ __('hr::hr.edit_hr_setting') }}
                    </a>
                @endcan
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="font-hold fw-bold">{{ __('hr::hr.company_max_leave_percentage') }} (%)</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.created_at') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.updated_at') }}</th>
                            @can('edit HR Settings')
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-hold fw-bold">
                                <span class="badge bg-success fs-6">{{ number_format((float) ($setting->company_max_leave_percentage ?? 0), 2) }}%</span>
                            </td>
                            <td class="font-hold fw-bold">{{ optional($setting->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="font-hold fw-bold">{{ optional($setting->updated_at)->format('Y-m-d H:i') }}</td>
                            @can('edit HR Settings')
                                <td>
                                    <a href="{{ route('hr.settings.edit') }}" class="btn btn-sm btn-warning font-hold fw-bold">
                                        <i class="fas fa-edit"></i> {{ __('hr::hr.edit') }}
                                    </a>
                                </td>
                            @endcan
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


    </div>
@endsection
