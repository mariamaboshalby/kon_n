{{-- تحميل Chart.js مرة واحدة فقط --}}
@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endonce

<!-- Charts Grid -->
<div class="row g-3 mb-4">
    @for($i = 1; $i <= 20; $i++)
        <div class="col-12 col-md-6 col-xl-4">
            @include('dashboard.components.chart' . $i)
        </div>
    @endfor
</div>
