<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Massar') }} | {{ __('dashboard.dashboard') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">

    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap & Core CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app-rtl.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Masar Themes -->
    <link rel="stylesheet" href="{{ asset('css/themes/masar-themes.css') }}">

    <!-- Dashboard Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard-main.css') }}">

    <!-- Lucide Icons CDN -->
    <script src="{{ asset('assets/js/lucide.js') }}"></script>

    @livewireStyles
</head>

<body class="theme-neumorphism-lite">
    <script>
        (function() {
            var k = 'masar_theme';
            var v;
            try {
                v = localStorage.getItem(k);
            } catch (e) {
                v = null;
            }
            var t = (v && ['classic', 'mint-green', 'dark', 'monokai'].indexOf(v) !== -1) ? v : 'classic';
            document.body.classList.add('theme-' + t);
        })();
    </script>
    <!-- Animated Doodles Background - Geometric Shapes, Currency & ERP Icons -->
    <div class="doodles-container">
        <!-- Dollar Sign Icon -->
        <svg class="doodle doodle-1" width="80" height="80" viewBox="0 0 80 80"
            xmlns="http://www.w3.org/2000/svg">
            <circle cx="40" cy="40" r="30" stroke="#239d77" stroke-width="2.5" fill="none"
                opacity="0.25" />
            <text x="40" y="50" font-family="Arial, sans-serif" font-size="40" font-weight="bold" fill="#239d77"
                opacity="0.3" text-anchor="middle">$</text>
        </svg>

        <!-- Chart/Graph Icon -->
        <svg class="doodle doodle-2" width="100" height="100" viewBox="0 0 100 100"
            xmlns="http://www.w3.org/2000/svg">
            <rect x="20" y="60" width="15" height="30" fill="#34d3a3" opacity="0.25" />
            <rect x="40" y="40" width="15" height="50" fill="#239d77" opacity="0.25" />
            <rect x="60" y="30" width="15" height="60" fill="#2ba88a" opacity="0.25" />
            <line x1="15" y1="80" x2="85" y2="80" stroke="#239d77" stroke-width="2"
                opacity="0.2" />
            <line x1="15" y1="80" x2="15" y2="15" stroke="#239d77" stroke-width="2"
                opacity="0.2" />
        </svg>

        <!-- Box/Inventory Icon -->
        <svg class="doodle doodle-3" width="90" height="90" viewBox="0 0 90 90"
            xmlns="http://www.w3.org/2000/svg">
            <rect x="25" y="25" width="40" height="40" stroke="#34d3a3" stroke-width="2.5" fill="none"
                opacity="0.25" />
            <rect x="30" y="30" width="30" height="30" stroke="#239d77" stroke-width="2" fill="none"
                opacity="0.2" />
            <line x1="25" y1="25" x2="35" y2="15" stroke="#239d77" stroke-width="2"
                opacity="0.2" />
            <line x1="65" y1="25" x2="75" y2="15" stroke="#239d77" stroke-width="2"
                opacity="0.2" />
            <line x1="35" y1="15" x2="75" y2="15" stroke="#239d77" stroke-width="2"
                opacity="0.2" />
        </svg>

        <!-- Euro Sign -->
        <svg class="doodle doodle-4" width="70" height="70" viewBox="0 0 70 70"
            xmlns="http://www.w3.org/2000/svg">
            <circle cx="35" cy="35" r="25" stroke="#2ba88a" stroke-width="2.5" fill="none"
                opacity="0.25" />
            <text x="35" y="45" font-family="Arial, sans-serif" font-size="35" font-weight="bold" fill="#2ba88a"
                opacity="0.3" text-anchor="middle">€</text>
        </svg>

        <!-- Database Icon -->
        <svg class="doodle doodle-5" width="110" height="110" viewBox="0 0 110 110"
            xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="55" cy="30" rx="35" ry="12" stroke="#34d3a3"
                stroke-width="2.5" fill="none" opacity="0.25" />
            <ellipse cx="55" cy="55" rx="35" ry="12" stroke="#239d77"
                stroke-width="2.5" fill="none" opacity="0.25" />
            <ellipse cx="55" cy="80" rx="35" ry="12" stroke="#2ba88a"
                stroke-width="2.5" fill="none" opacity="0.25" />
            <line x1="20" y1="30" x2="20" y2="80" stroke="#239d77" stroke-width="2.5"
                opacity="0.2" />
            <line x1="90" y1="30" x2="90" y2="80" stroke="#239d77" stroke-width="2.5"
                opacity="0.2" />
        </svg>

        <!-- File/Document Icon -->
        <svg class="doodle doodle-6" width="85" height="85" viewBox="0 0 85 85"
            xmlns="http://www.w3.org/2000/svg">
            <path d="M25 20 L25 65 L60 65 L60 35 L45 35 L45 20 Z" stroke="#239d77" stroke-width="2.5" fill="none"
                opacity="0.25" />
            <line x1="45" y1="20" x2="60" y2="35" stroke="#239d77" stroke-width="2.5"
                opacity="0.25" />
            <line x1="30" y1="40" x2="55" y2="40" stroke="#34d3a3" stroke-width="2"
                opacity="0.2" />
            <line x1="30" y1="50" x2="55" y2="50" stroke="#34d3a3" stroke-width="2"
                opacity="0.2" />
        </svg>

        <!-- Geometric Shapes: Triangle -->
        <svg class="doodle doodle-7" width="75" height="75" viewBox="0 0 75 75"
            xmlns="http://www.w3.org/2000/svg">
            <polygon points="37.5,15 60,55 15,55" stroke="#34d3a3" stroke-width="2.5" fill="none"
                opacity="0.25" />
            <circle cx="37.5" cy="40" r="8" fill="#239d77" opacity="0.2" />
        </svg>

        <!-- Geometric Shapes: Hexagon -->
        <svg class="doodle doodle-8" width="95" height="95" viewBox="0 0 95 95"
            xmlns="http://www.w3.org/2000/svg">
            <polygon points="47.5,15 70,30 70,55 47.5,70 25,55 25,30" stroke="#2ba88a" stroke-width="2.5"
                fill="none" opacity="0.25" />
            <circle cx="47.5" cy="42.5" r="12" stroke="#239d77" stroke-width="2" fill="none"
                opacity="0.2" />
        </svg>

        <!-- Coin/Circle with $ -->
        <svg class="doodle doodle-9" width="65" height="65" viewBox="0 0 65 65"
            xmlns="http://www.w3.org/2000/svg">
            <circle cx="32.5" cy="32.5" r="25" stroke="#239d77" stroke-width="2.5" fill="#239d77"
                opacity="0.15" />
            <text x="32.5" y="42" font-family="Arial, sans-serif" font-size="30" font-weight="bold" fill="#239d77"
                opacity="0.4" text-anchor="middle">$</text>
        </svg>

        <!-- Square Grid -->
        <svg class="doodle doodle-10" width="100" height="100" viewBox="0 0 100 100"
            xmlns="http://www.w3.org/2000/svg">
            <rect x="20" y="20" width="20" height="20" stroke="#34d3a3" stroke-width="2" fill="none"
                opacity="0.25" />
            <rect x="50" y="20" width="20" height="20" stroke="#239d77" stroke-width="2" fill="none"
                opacity="0.25" />
            <rect x="20" y="50" width="20" height="20" stroke="#2ba88a" stroke-width="2" fill="none"
                opacity="0.25" />
            <rect x="50" y="50" width="20" height="20" stroke="#34d3a3" stroke-width="2" fill="none"
                opacity="0.25" />
        </svg>
    </div>

    <!-- Navbar Section - نفس الـ topbar في باقي المشروع -->
    <div class="topbar">
        <nav class="navbar-custom d-flex justify-content-between align-items-center">

            {{-- اليمين: اللوجو واسم الشركة --}}
            <a href="{{ route('admin.dashboard') }}"
                class="navbar-brand-section d-flex align-items-center gap-2 text-decoration-none">
                <div class="navbar-logo-icon">
                    <img src="{{ asset('assets/images/masarlogo.jpg') }}" alt="مسار" class="navbar-logo-img">
                </div>
                <div class="navbar-brand-text">
                    <span class="navbar-brand-name">مسار<span class="navbar-brand-erp">ERP</span></span>
                    <span class="navbar-brand-tagline">{{ __('dashboard.brand_tagline') }}</span>
                </div>
            </a>

            {{-- الشمال: الأيقونات --}}
            <ul class="list-unstyled topbar-nav mb-0 d-flex align-items-center">

                {{-- بحث سريع في الوحدات --}}
                <li class="ms-3 d-none d-md-block">
                    <div class="search-container-navbar">
                        <svg class="search-icon-navbar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input type="text" id="searchInput" class="search-input-navbar"
                            placeholder="{{ __('dashboard.search_modules') }}"
                            autocomplete="off">
                        <span class="search-count-navbar" id="searchCount"></span>
                    </div>
                </li>

                <x-notifications::notifications />

                <!-- مبدل اللغة -->
                <li class="ms-3">
                    @livewire('language-switcher')
                </li>

                {{-- Theme switcher dropdown --}}
                <li class="dropdown ms-3" data-masar-theme-dropdown>
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false" title="{{ __('Theme') }}"
                        style="color: #34d3a3;">
                        <i class="fas fa-palette fa-2x" style="color: #34d3a3;"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-start">
                        <a class="dropdown-item" href="#" data-masar-theme="classic"><i
                                class="fas fa-palette me-1"></i> Classic (Bootstrap)</a>
                        <a class="dropdown-item" href="#" data-masar-theme="mint-green"><i
                                class="fas fa-leaf me-1"></i> Mint Green</a>
                        <a class="dropdown-item" href="#" data-masar-theme="dark"><i
                                class="fas fa-moon me-1"></i> Dark Mode</a>
                        <a class="dropdown-item" href="#" data-masar-theme="monokai"><i
                                class="fas fa-code me-1"></i> Monokai</a>
                    </div>
                </li>

                @can('view Settings Control')
                <li class="ms-1">
                    <a title="{{ __('navigation.users') }}" href="{{ route('mysettings.index') }}"
                        class="nav-link transition-base" style="color: #34d3a3;">
                        <i class="fas fa-cog fa-2x" style="color: #34d3a3;"></i>
                    </a>
                </li>
                @endcan
                <li class="ms-1">
                    <button type="button" class="btn btn-lg transition-base logout-btn"
                        title="{{ __('navigation.logout') }}" onclick="confirmLogout()"
                        style="background: none; border: none; color: #34d3a3; cursor: pointer;">
                        <i class="fas fa-sign-out-alt fa-2x" style="color: #34d3a3;"></i>
                    </button>
                    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>


        </nav>
    </div>

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
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
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

    <style>
        /* Navbar Styles */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
        }

        .navbar-custom {
            padding: 0.75rem 1.5rem;
        }

        /* Search Bar في الـ Navbar */
        .search-container-navbar {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 400px;
        }

        .search-icon-navbar {
            position: absolute;
            right: 15px;
            width: 20px;
            height: 20px;
            color: #34d3a3;
            pointer-events: none;
        }

        .search-input-navbar {
            width: 100%;
            padding: 10px 45px 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input-navbar:focus {
            outline: none;
            border-color: #34d3a3;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 211, 163, 0.1);
        }

        .search-count-navbar {
            position: absolute;
            left: 15px;
            font-size: 12px;
            color: #34d3a3;
            font-weight: 600;
            display: none;
        }

        .animated-popup {
            border-radius: 15px !important;
            box-shadow: 0 10px 40px rgba(52, 211, 163, 0.3) !important;
        }

        .btn-confirm-logout {
            border-radius: 8px !important;
            font-weight: bold !important;
            padding: 10px 25px !important;
        }

        .btn-cancel-logout {
            border-radius: 8px !important;
            font-weight: bold !important;
            padding: 10px 25px !important;
        }

        .logout-btn:hover i {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }

        /* Brand Styles */
        .navbar-logo-icon {
            flex-shrink: 0;
            filter: drop-shadow(0 2px 6px rgba(52, 211, 163, 0.4));
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .navbar-logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 8px;
        }

        .navbar-brand-section:hover .navbar-logo-icon {
            transform: scale(1.08) rotate(-3deg);
            filter: drop-shadow(0 4px 10px rgba(52, 211, 163, 0.6));
        }

        .navbar-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .navbar-brand-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1a7a5e;
            letter-spacing: -0.5px;
        }

        .navbar-brand-erp {
            font-style: italic;
            font-size: 0.8em;
            font-weight: 600;
            color: #34d3a3;
            letter-spacing: 1px;
            margin-inline-start: 2px;
        }

        .navbar-brand-tagline {
            font-size: 0.65rem;
            color: #6b9e8e;
            font-weight: 500;
            letter-spacing: 0.3px;
            opacity: 0.85;
        }

        body.theme-dark .navbar-brand-name,
        body.theme-monokai .navbar-brand-name {
            color: #34d3a3;
        }

        body.theme-dark .navbar-brand-tagline,
        body.theme-monokai .navbar-brand-tagline {
            color: #a0c4b8;
        }

        /* Fix navbar styling */
        .topbar .nav-link {
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .topbar .topbar-nav li {
            list-style: none;
        }

        /* Fix body padding to account for fixed navbar */
        body {
            padding-top: 70px !important;
        }

        /* Fix dashboard container to not overlap with navbar */
        .dashboard-container {
            margin-top: 0 !important;
            padding-top: 20px !important;
        }

        /* Hide old header section */
        .header-section {
            display: none !important;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .search-container-navbar {
                min-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .search-container-navbar {
                display: none;
            }
        }
    </style>

    <div class="dashboard-container">

        <!-- كروت الإحصائيات -->
        <div class="stats-cards-section">
            <div class="row g-3 stats-cards-row">
                <!-- كرت العملاء -->
                <div class="col-lg-6 col-md-6 stats-card-col">
                    <div class="card border-0 shadow-lg h-100 stats-card stats-card-clients">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="stats-card-content">
                                    <p class="stats-card-label">{{ __('dashboard.total_clients') }}</p>
                                    <h2 class="stats-card-value">{{ number_format($totalClients ?? 0) }}</h2>
                                    <p class="stats-card-subtitle">
                                        <i data-lucide="trending-up"></i>
                                        {{ __('dashboard.total_clients_label') }}
                                    </p>
                                </div>
                                <div class="stat-icon-wrapper">
                                    <i data-lucide="users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-decoration card-decoration-top"></div>
                        <div class="card-decoration card-decoration-bottom"></div>
                    </div>
                </div>

                <!-- كرت مرات الدخول -->
                <div class="col-lg-6 col-md-6 stats-card-col">
                    <div class="card border-0 shadow-lg h-100 stats-card stats-card-logins">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="stats-card-content">
                                    <p class="stats-card-label">{{ __('dashboard.total_logins') }}</p>
                                    <h2 class="stats-card-value">{{ number_format($totalLogins ?? 0) }}</h2>
                                    <p class="stats-card-subtitle">
                                        <i data-lucide="activity"></i>
                                        {{ __('dashboard.total_sessions') }}
                                    </p>
                                </div>
                                <div class="stat-icon-wrapper">
                                    <i data-lucide="log-in"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-decoration card-decoration-top"></div>
                        <div class="card-decoration card-decoration-bottom"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- أيقونات البرنامج - مجموعات -->
        <div class="apps-groups-section" id="appsGroupsSection">
            <div class="row g-4">
                {{-- ===== المجموعة 6: العمليات الأساسية ===== --}}
                <div class="col-12">
                    <div class="module-group h-100" data-group="core">
                        <div class="module-group-header module-group-header-blue">
                            <i data-lucide="layout-grid"></i>
                            <span>{{ __('dashboard.group_core') }}</span>
                            <small>{{ __('dashboard.group_core_desc') }}</small>
                        </div>
                        <div class="module-group-icons row g-2">
                            {{-- الاعدادات --}}
                            @can('view settings')
                            <div class="col-auto">
                                <a href="{{ route('mysettings.index') }}" class="app-icon-large icon-bg-green"
                                    title="{{ __('dashboard.settings') }}">
                                    <div class="icon-wrapper"><i data-lucide="settings"></i></div>
                                    <p>{{ __('dashboard.settings') }}</p>
                                </a>
                            </div>
                            @endcan
                            {{-- الصلاحيات --}}
                            @canany(['view roles', 'view branches', 'view settings', 'view login-history', 'view active-sessions', 'view activity-logs'])
                            <div class="col-auto">
                                <a href="{{ route('users.index') }}" class="app-icon-large icon-bg-green"
                                    title="{{ __('dashboard.permissions') }}">
                                    <div class="icon-wrapper"><i data-lucide="key"></i></div>
                                    <p>{{ __('dashboard.permissions') }}</p>
                                </a>
                            </div>
                            @endcanany
                        </div>
                    </div>
                </div>

                {{-- ===== المجموعة 2: دورة المبيعات ===== --}}
                <div class="col-12">
                    <div class="module-group h-100" data-group="sales">
                        @canany(['view CRM', 'view CRM Statistics','view CRM Clients'])
                        <div class="module-group-header module-group-header-orange">
                            <i data-lucide="trending-up"></i>
                            <span>{{ __('dashboard.group_sales') }}</span>
                            <small>{{ __('dashboard.group_sales_desc') }}</small>
                        </div>
                        @endcanany
                        <div class="module-group-icons row g-2">
                            {{-- crm --}}
                            @canany(['view CRM', 'view CRM Statistics','view CRM Clients'])
                            <div class="col-auto">
                                <a href="{{ route('statistics.index') }}" class="app-icon-large icon-bg-green"
                                    title="{{ __('dashboard.crm') }}">
                                    <div class="icon-wrapper"><i data-lucide="user-cog"></i></div>
                                    <p>{{ __('dashboard.crm') }}</p>
                                </a>
                            </div>
                            @endcanany
                        </div>
                    </div>
                </div>

                {{-- ===== المجموعة 4: العمليات المتخصصة والإنتاج ===== --}}
                @canany(['view Projects', 'view Inquiries'])
                <div class="col-12">
                    <div class="module-group h-100" data-group="operations">
                        <div class="module-group-header module-group-header-pink">
                            <i data-lucide="factory"></i>
                            <span>{{ __('dashboard.group_operations') }}</span>
                            <small>{{ __('dashboard.group_operations_desc') }}</small>
                        </div>
                        <div class="module-group-icons row g-2">
                            {{-- المشاريع --}}
                            @can('view Projects')
                            @if (Route::has('projects.index'))
                            <div class="col-auto">
                                <a href="{{ route('projects.index') }}" class="app-icon-large icon-bg-green"
                                    title="{{ __('dashboard.projects') }}">
                                    <div class="icon-wrapper"><i data-lucide="kanban"></i></div>
                                    <p>{{ __('dashboard.projects') }}</p>
                                </a>
                            </div>
                            @endif
                            @endcan
                            {{-- Inquiries --}}
                            @can('view Inquiries')
                            <div class="col-auto">
                                <a href="{{ route('inquiries.index') }}" class="app-icon-large icon-bg-green"
                                    title="{{ __('dashboard.inquiries') }}">
                                    <div class="icon-wrapper"><i data-lucide="layers"></i></div>
                                    <p>{{ __('dashboard.inquiries') }}</p>
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
                @endcanany

                {{-- ===== المجموعة 5: الإدارة والرقابة ===== --}}
                @canany([
                    'view Employees',
                    'view progress-recyclebin',
                    'view progress-project-types',
                    'view progress-project-templates',
                    'view progress-item-statuses',
                    'view progress-work-items',
                    'view progress-work-item-categories',
                    'view daily-progress',
                    'view progress-issues',
                    'view progress-projects',
                    'view progress-dashboard',
                ])
                <div class="col-12">
                    <div class="module-group h-100" data-group="management">
                        <div class="module-group-header module-group-header-cyan">
                            <i data-lucide="bar-chart-2"></i>
                            <span>{{ __('dashboard.group_management') }}</span>
                            <small>{{ __('dashboard.group_management_desc') }}</small>
                        </div>
                        <div class="module-group-icons row g-2">
                            {{-- التقدم اليومي --}}
                            @canany(['view progress-recyclebin', 'view progress-project-types', 'view progress-project-templates', 'view progress-item-statuses', 'view progress-work-items', 'view progress-work-item-categories', 'view daily-progress', 'view progress-issues', 'view progress-projects', 'view progress-dashboard'])
                            @if (Route::has('progress.dashboard'))
                            <div class="col-auto">
                                <a href="{{ route('progress.dashboard') }}" class="app-icon-large icon-bg-green"
                                    title="{{ __('dashboard.daily_progress') }}">
                                    <div class="icon-wrapper"><i data-lucide="bar-chart-3"></i></div>
                                    <p>{{ __('dashboard.daily_progress') }}</p>
                                </a>
                            </div>
                            @endif
                            @endcanany
                            {{-- الموارد البشريه --}}
                            @can('view Employees')
                            <div class="col-auto">
                                <a href="{{ route('employees.index') }}" class="app-icon-large icon-bg-green"
                                    title="{{ __('dashboard.hr') }}">
                                    <div class="icon-wrapper"><i data-lucide="users"></i></div>
                                    <p>{{ __('dashboard.hr') }}</p>
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
                @endcanany

                {{-- hidden container للـ search --}}
                <div class="apps-icons-row d-none" id="searchResultsRow">
                    <div class="d-flex" id="searchResultsGrid">
                        {{-- يتم ملؤه ديناميكياً عبر JS --}}

                        {{-- الصلاحيات --}}
                        <div class="app-icon-group">
                            @canany([
                            'view roles',
                            'view branches',
                            'view settings',
                            'view login-history',
                            'view
                            active-sessions',
                            'view activity-logs',
                            ])
                            <a href="{{ route('users.index') }}" class="app-icon-large icon-bg-green">
                                <div class="icon-wrapper">
                                    <i data-lucide="key"></i>
                                </div>
                                <p>{{ __('dashboard.permissions') }}</p>
                            </a>
                            @endcanany
                        </div>

                        {{-- الاعدادات --}}
                        <div class="app-icon-group">
                            @can('view settings')
                            <a href="{{ route('mysettings.index') }}" class="app-icon-large icon-bg-green">
                                <div class="icon-wrapper">
                                    <i data-lucide="settings"></i>
                                </div>
                                <p>{{ __('dashboard.settings') }}</p>
                            </a>
                            @endcan
                        </div>


                        {{-- crm --}}
                        <div class="app-icon-group">
                            @canany(['view CRM', 'view CRM Statistics'])
                            <a href="{{ route('statistics.index') }}" class="app-icon-large icon-bg-green">
                                <div class="icon-wrapper">
                                    <i data-lucide="user-cog"></i>
                                </div>
                                <p>{{ __('dashboard.crm') }}</p>
                            </a>
                            @endcanany
                        </div>

                        {{-- المشاريع --}}
                        <div class="app-icon-group">
                            @can('view Projects')
                            @if (Route::has('projects.index'))
                            <a href="{{ route('projects.index') }}" class="app-icon-large icon-bg-green">
                                <div class="icon-wrapper">
                                    <i data-lucide="kanban"></i>
                                </div>
                                <p>{{ __('dashboard.projects') }}</p>
                            </a>
                            @endif
                            @endcan
                        </div>

                        {{-- التقدم اليومي --}}
                        <div class="app-icon-group">
                            @canany(['view progress-recyclebin', 'view progress-project-types', 'view
                            progress-project-templates', 'view progress-item-statuses', 'view progress-work-items', 'view
                            progress-work-item-categories', 'view daily-progress', 'view progress-issues', 'view
                            progress-projects', 'view progress-dashboard'])
                            @if (Route::has('progress.dashboard'))
                            <a href="{{ route('progress.dashboard') }}" class="app-icon-large icon-bg-green">
                                <div class="icon-wrapper">
                                    <i data-lucide="bar-chart-3"></i>
                                </div>
                                <p>{{ __('dashboard.daily_progress') }}</p>
                            </a>
                            @endif
                            @endcanany
                        </div>


                        {{-- الموارد البشريه --}}
                        <div class="app-icon-group">
                            @can('view Employees')
                            <a href="{{ route('employees.index') }}" class="app-icon-large icon-bg-green">
                                <div class="icon-wrapper">
                                    <i data-lucide="users"></i>
                                </div>
                                <p>{{ __('dashboard.hr') }}</p>
                            </a>
                            @endcan
                        </div>

                        {{-- Inquiries --}}
                        <div class="app-icon-group">
                            @can('view Inquiries')
                            <a href="{{ route('inquiries.index') }}" class="app-icon-large icon-bg-green">
                                <div class="icon-wrapper">
                                    <i data-lucide="layers"></i>
                                </div>
                                <p>{{ __('dashboard.inquiries') }}</p>
                            </a>
                            @endcan
                        </div>


                        {{-- المشاريع --}}

                    </div>
                </div>

                <!-- الجداول (3 في الصف) -->
                <div class="tables-section" style="margin-top: 3rem;">

                    <div class="row g-4">
                        <!-- آخر 5 عمليات تسجيل دخول -->
                        @canany(['view login-history', 'view active-sessions', 'view activity-logs'])
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white border-bottom" style="padding: 1rem 1.25rem;">
                                    <h5 class="mb-0 fw-bold tables-section-title">
                                        <i data-lucide="log-in"
                                            style="width: 20px; height: 20px; margin-left: 8px; vertical-align: middle;"></i>
                                        {{ __('dashboard.recent_logins') }}
                                    </h5>
                                </div>
                                <div class="card-body" style="padding: 0;">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('dashboard.user') }}</th>
                                                    <th>{{ __('dashboard.date') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($recentLogins ?? [] as $login)
                                                <tr>
                                                    <td><strong>{{ $login->user->name ?? '-' }}</strong></td>
                                                    <td style="font-size: 0.875rem;">
                                                        {{ $login->login_at ? $login->login_at->format('Y-m-d H:i') : '-' }}
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-5">{{ __('dashboard.no_data') }}
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endcanany

                    </div>
                </div>
            </div>
        </div>
    </div>{{-- end dashboard-container --}}

            {{-- Masar theme switcher --}}
            <script src="{{ asset('js/theme-switcher.js') }}"></script>
            <script>
                (function() {
                    if (typeof MasarThemeSwitcher !== 'undefined') {
                        // Bind to dropdown items instead of select
                        MasarThemeSwitcher.bindDropdown('[data-masar-theme-dropdown]');
                    }
                })();
            </script>

            <script>
                // Initialize Lucide icons when DOM is loaded
                function initLucide() {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    initLucide();

                    // Re-initialize after Livewire updates
                    document.addEventListener('livewire:navigated', initLucide);
                    document.addEventListener('livewire:update', initLucide);

                    // Also hook into Livewire's commit cycle
                    if (typeof Livewire !== 'undefined') {
                        Livewire.hook('commit', ({
                            succeed
                        }) => {
                            succeed(() => {
                                setTimeout(initLucide, 50);
                            });
                        });
                    }

                    // Search functionality
                    const searchInput = document.getElementById('searchInput');
                    const searchCount = document.getElementById('searchCount');
                    const appsGroupsSection = document.getElementById('appsGroupsSection');

                    function performSearch() {
                        if (!searchInput || !appsGroupsSection) return;

                        const searchTerm = searchInput.value.toLowerCase().trim();
                        let visibleCount = 0;

                        // Search in all module groups
                        const moduleGroups = appsGroupsSection.querySelectorAll('.module-group');

                        moduleGroups.forEach(function(group) {
                            const icons = group.querySelectorAll('.app-icon-large');
                            let groupHasVisible = false;

                            icons.forEach(function(icon) {
                                const appText = icon.querySelector('p');
                                const text = appText ? appText.textContent.toLowerCase().trim() : '';
                                const matches = searchTerm === '' || text.includes(searchTerm);

                                if (matches) {
                                    icon.style.display = '';
                                    visibleCount++;
                                    groupHasVisible = true;
                                } else {
                                    icon.style.display = 'none';
                                }
                            });

                            // Hide entire group if no icons match
                            group.style.display = (searchTerm === '' || groupHasVisible) ? '' : 'none';
                        });

                        // Update search count
                        if (searchCount) {
                            if (searchTerm !== '') {
                                searchCount.textContent = visibleCount + ' {{ __("dashboard.results") }}';
                                searchCount.style.display = 'block';
                            } else {
                                searchCount.style.display = 'none';
                            }
                        }
                    }

                    if (searchInput) {
                        searchInput.addEventListener('input', performSearch);
                        searchInput.addEventListener('paste', function() {
                            setTimeout(performSearch, 10);
                        });
                    }
                });

                // Reinitialize icons if Lucide loads after DOM
                window.addEventListener('load', function() {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            </script>

            <!-- Bootstrap JS -->
            <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

            <!-- SweetAlert2 for logout confirmation -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            @livewireScripts
</body>

</html>