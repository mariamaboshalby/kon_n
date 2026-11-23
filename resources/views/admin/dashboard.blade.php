<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
@include('admin.partials.head')

<body class="@yield('body_class')">
    {{-- Apply saved theme immediately to avoid flash --}}
    <script>
    (function(){var k='masar_theme';var v;try{v=localStorage.getItem(k);}catch(e){v=null;}
    var t=(v&&['classic','mint-green','dark','monokai'].indexOf(v)!==-1)?v:'classic';
    document.body.classList.add('theme-'+t);
    })();
    </script>
    {{-- YouTube-style Progress Bar Loader --}}
    <div id="page-loader" class="page-loader">
        <div class="loader-bar"></div>
    </div>

    @hasSection('sidebar')

        <div class="left-sidenav">
            <div class="menu-content h-100" data-simplebar>
                <ul class="metismenu left-sidenav-menu">
         
                    <li class="nav-item border-bottom pb-2 mb-2 text-center">
                        <a href="{{ route('home') }}" class="fw-bold text-primary font-16">
                            {{ config('public_settings.campany_name') }}
                        </a>
                    </li>

                    <li class="nav-item border-bottom pb-1 mb-2">
                        <a href="{{ route('admin.dashboard') }}"
                            class="nav-link d-flex align-items-center gap-2 transition-base {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                            style="{{ request()->routeIs('admin.dashboard') ? 'background-color: rgba(52, 211, 163, 0.1); color: #34d3a3; font-weight: 600;' : 'color: var(--color-text-secondary);' }}">
                            <i data-feather="home" style="color: {{ request()->routeIs('admin.dashboard') ? '#34d3a3' : '#6b7280' }}" class="menu-icon"></i>
                            {{ __('navigation.home') }}
                        </a>
                    </li>


                    {{-- Sidebar Content: يتم تعريفه في كل صفحة --}}
                    @yield('sidebar')
                </ul>
            </div>

            {{-- زر تسجيل الخروج --}}
            <div class="p-3 border-top text-center">
                <button type="button" class="btn transition-base logout-btn w-100"
                    title="{{ __('navigation.logout') }}" onclick="confirmLogout()"
                    style="background: none; border: 1px solid #34d3a3; color: #34d3a3; cursor: pointer; border-radius: 8px; padding: 10px;">
                    <i class="fas fa-sign-out-alt me-2" style="color: #34d3a3;"></i>
                    {{ __('navigation.logout') }}
                </button>
            </div>
        </div>
    @else
        {{-- Default Sidebar: للصفحات القديمة اللي ما عندهاش dynamic sidebar --}}
        @include('admin.partials.sidebar-default')
    @endif

    <div class="page-wrapper">
        @hasSection('hide_topbar')@else
        @include('admin.partials.topbar')
        @endif
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    @include('sweetalert::alert')
                    @yield('content')
                </div>
            </div>
            @hasSection('hide_footer') @else
            @include('admin.partials.footer')
            @endif
        </div>
    </div>
    @include('admin.partials.scripts')
    @yield('script')

    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display: none;">
        @csrf
    </form>

    <script>
        function confirmLogout() {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "سيتم تسجيل خروجك من النظام",
                icon: 'warning',
                iconColor: '#34d3a3',
                showCancelButton: true,
                confirmButtonColor: '#34d3a3',
                cancelButtonColor: '#d33',
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> نعم، تسجيل الخروج',
                cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
                reverseButtons: true,
                customClass: {
                    popup: 'animated-popup',
                    confirmButton: 'btn-confirm-logout',
                    cancelButton: 'btn-cancel-logout'
                },
                showClass: { popup: 'animate__animated animate__fadeInDown' },
                hideClass: { popup: 'animate__animated animate__fadeOutUp' }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'جاري تسجيل الخروج...',
                        html: '<div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 1000
                    });
                    setTimeout(() => {
                        document.getElementById('logout-form').submit();
                    }, 1000);
                }
            });
        }
    </script>
</body>

</html>
