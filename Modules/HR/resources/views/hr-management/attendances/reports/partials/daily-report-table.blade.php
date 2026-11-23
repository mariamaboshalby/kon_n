@if(count($rows) > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="px-4 py-3">#</th>
                    @if(in_array('date', $columns))
                        <th class="py-3">{{ __('hr.date') }}</th>
                    @endif
                    @if(in_array('name', $columns))
                        <th class="py-3">{{ __('hr.employee_name') }}</th>
                    @endif
                    @if(in_array('department', $columns))
                        <th class="py-3">{{ __('hr.department') }}</th>
                    @endif
                    @if(in_array('shift', $columns))
                        <th class="py-3">{{ __('hr.shift') }}</th>
                    @endif
                    @if(in_array('check_in_time', $columns))
                        <th class="py-3 text-center">{{ __('hr.check_in_time') }}</th>
                    @endif
                    @if(in_array('check_out_time', $columns))
                        <th class="py-3 text-center">{{ __('hr.check_out_time') }}</th>
                    @endif
                    @if(in_array('late_minutes', $columns))
                        <th class="py-3 text-center">{{ __('hr.late_hours') }}</th>
                    @endif
                    @if(in_array('leave_type', $columns))
                        <th class="py-3">{{ __('hr.leave_type') }}</th>
                    @endif
                    @if(in_array('leave_start', $columns))
                        <th class="py-3">{{ __('hr.leave_start_date') }}</th>
                    @endif
                    @if(in_array('leave_end', $columns))
                        <th class="py-3">{{ __('hr.leave_end_date') }}</th>
                    @endif
                    @if(in_array('leave_duration', $columns))
                        <th class="py-3 text-center">{{ __('hr.leave_days_count') }}</th>
                    @endif
                    @if(in_array('is_paid', $columns))
                        <th class="py-3 text-center">{{ __('hr.paid') }}</th>
                    @endif
                    @if(in_array('errand_title', $columns))
                        <th class="py-3">{{ __('hr.errands') }}</th>
                    @endif
                    @if(in_array('errand_start', $columns))
                        <th class="py-3">{{ __('hr.start_date') }}</th>
                    @endif
                    @if(in_array('errand_end', $columns))
                        <th class="py-3">{{ __('hr.end_date') }}</th>
                    @endif
                    @if(in_array('permission_reason', $columns))
                        <th class="py-3">{{ __('hr.reason') }}</th>
                    @endif
                    @if(in_array('permission_date', $columns))
                        <th class="py-3">{{ __('hr.date') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                    <tr>
                        <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                        @if(in_array('date', $columns))
                            <td class="py-3">
                                <span class="badge bg-light text-dark border">{{ $row['date'] ?? '' }}</span>
                            </td>
                        @endif
                        @if(in_array('name', $columns))
                            <td class="py-3">
                                <div class="fw-bold">{{ $row['name'] }}</div>
                                <small class="text-muted">#{{ $row['finger_print_id'] }}</small>
                            </td>
                        @endif
                        @if(in_array('department', $columns))
                            <td class="py-3 text-muted">{{ $row['department'] }}</td>
                        @endif
                        @if(in_array('shift', $columns))
                            <td class="py-3 text-muted">{{ $row['shift'] }}</td>
                        @endif
                        @if(in_array('check_in_time', $columns))
                            <td class="py-3 text-center">
                                @if(!empty($row['check_in_time']))
                                    <span class="badge bg-success-subtle text-success fw-bold">
                                        <i class="las la-sign-in-alt me-1"></i>{{ $row['check_in_time'] }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endif
                        @if(in_array('check_out_time', $columns))
                            <td class="py-3 text-center">
                                @if(!empty($row['check_out_time']))
                                    <span class="badge bg-danger-subtle text-danger fw-bold">
                                        <i class="las la-sign-out-alt me-1"></i>{{ $row['check_out_time'] }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endif
                        @if(in_array('late_minutes', $columns))
                            <td class="py-3 text-center">
                                @if(!empty($row['late_minutes']) && $row['late_minutes'] > 0)
                                    <span class="badge bg-danger">
                                        {{ $row['late_minutes'] }} {{ __('hr.minutes') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endif
                        @if(in_array('leave_type', $columns))
                            <td class="py-3">
                                <span class="badge bg-info-subtle text-info">{{ $row['leave_type'] }}</span>
                            </td>
                        @endif
                        @if(in_array('leave_start', $columns))
                            <td class="py-3">{{ $row['leave_start'] }}</td>
                        @endif
                        @if(in_array('leave_end', $columns))
                            <td class="py-3">{{ $row['leave_end'] }}</td>
                        @endif
                        @if(in_array('leave_duration', $columns))
                            <td class="py-3 text-center">
                                <span class="badge bg-secondary">{{ $row['leave_duration'] }} {{ __('hr.days') }}</span>
                            </td>
                        @endif
                        @if(in_array('is_paid', $columns))
                            <td class="py-3 text-center">
                                @if($row['is_paid'])
                                    <span class="badge bg-success">{{ __('hr.paid') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ __('hr.unpaid') }}</span>
                                @endif
                            </td>
                        @endif
                        @if(in_array('errand_title', $columns))
                            <td class="py-3 fw-bold">{{ $row['errand_title'] }}</td>
                        @endif
                        @if(in_array('errand_start', $columns))
                            <td class="py-3">{{ $row['errand_start'] }}</td>
                        @endif
                        @if(in_array('errand_end', $columns))
                            <td class="py-3">{{ $row['errand_end'] }}</td>
                        @endif
                        @if(in_array('permission_reason', $columns))
                            <td class="py-3 text-muted">{{ $row['permission_reason'] ?: '-' }}</td>
                        @endif
                        @if(in_array('permission_date', $columns))
                            <td class="py-3">{{ $row['permission_date'] }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-5">
        <i class="las la-check-circle fs-1 text-success opacity-50 d-block mb-2"></i>
        <p class="text-muted">{{ $empty }}</p>
    </div>
@endif
