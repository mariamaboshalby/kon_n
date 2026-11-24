<div class="topbar">
    <!-- Navbar -->
    <nav class="navbar-custom d-flex justify-content-between align-items-center">
        <ul class="list-unstyled topbar-nav mb-0 d-flex align-items-center">

            <!-- مبدل اللغة -->
            <li class="me-3">
                @livewire('language-switcher')
            </li>

            {{-- Notifications Bell --}}
            <x-notifications::notifications />


            <script>
                function toggleSidebarMenu() {
                    const sidebar = document.querySelector('.left-sidenav');
                    const pageWrapper = document.querySelector('.page-wrapper');
                    const toggleIcon = document.getElementById('sidebar-toggle-icon');
                    
                    if (!sidebar || !pageWrapper) {
                        console.warn('Sidebar or page wrapper not found');
                        return;
                    }

                    // Get current state
                    const isCurrentlyHidden = window.getComputedStyle(sidebar).display === 'none';
                    
                    // Toggle state
                    const newState = !isCurrentlyHidden;
                    localStorage.setItem('sidebarHidden', newState.toString());
                    
                    // Apply new state
                    if (newState) {
                        // Hide sidebar
                        sidebar.style.setProperty('display', 'none', 'important');
                        pageWrapper.style.setProperty('margin-left', '0', 'important');
                        pageWrapper.style.setProperty('margin-right', '0', 'important');
                        if (toggleIcon) {
                            toggleIcon.classList.remove('fa-times');
                            toggleIcon.classList.add('fa-bars');
                        }
                    } else {
                        // Show sidebar
                        sidebar.style.setProperty('display', 'block', 'important');
                        
                        // Reset wrappers only for desktop if needed, or keeping it responsive
                        if (window.innerWidth > 1024) {
                            pageWrapper.style.marginLeft = '';
                            pageWrapper.style.marginRight = '';
                        }
                        
                        if (toggleIcon) {
                            toggleIcon.classList.remove('fa-bars');
                            toggleIcon.classList.add('fa-times');
                        }
                    }
                }

                // Update icon state based on sidebar visibility
                function updateSidebarToggleIcon() {
                    const sidebar = document.querySelector('.left-sidenav');
                    const toggleIcon = document.getElementById('sidebar-toggle-icon');
                    
                    if (!sidebar || !toggleIcon) {
                        return;
                    }

                    const isHidden = window.getComputedStyle(sidebar).display === 'none';
                    
                    if (isHidden) {
                        toggleIcon.classList.remove('fa-times');
                        toggleIcon.classList.add('fa-bars');
                    } else {
                        toggleIcon.classList.remove('fa-bars');
                        toggleIcon.classList.add('fa-times');
                    }
                }

                // Initialize icon state on page load
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(updateSidebarToggleIcon, 100);
                    });
                } else {
                    setTimeout(updateSidebarToggleIcon, 100);
                }

                // Also update on window load
                window.addEventListener('load', function() {
                    setTimeout(updateSidebarToggleIcon, 200);
                });
            </script>

        </ul><!--end topbar-nav-->

        <ul class="list-unstyled topbar-nav mb-0 d-flex align-items-center order-first">
            {{-- Sidebar Toggle Button --}}
            <li class="me-3">
                <button type="button" 
                        id="sidebar-toggle-btn" 
                        class="btn btn-lg transition-base sidebar-toggle-btn"
                        title="{{ __('إظهار/إخفاء القائمة الجانبية') }}"
                        onclick="toggleSidebarMenu()"
                        style="background: none; border: none; color: #34d3a3; cursor: pointer; padding: 8px 12px;">
                    <i id="sidebar-toggle-icon" class="fas fa-bars fa-2x" style="color: #34d3a3;"></i>
                </button>
            </li>
            
            <li>
                <button type="button"
                        id="help-center-btn"
                        class="btn btn-lg transition-base"
                        title="{{ __('مركز المساعدة') }} (F1)"
                        onclick="massarHelpCenter.open()"
                        style="background: none; border: none; color: #34d3a3; cursor: pointer; padding: 8px 12px;">
                    <i class="fas fa-book fa-2x" style="color: #34d3a3;"></i>
                </button>
            </li>

        </ul>
    </nav>
    <!-- end navbar-->
</div>

{{-- Help Center Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="helpCenterOffcanvas" aria-labelledby="helpCenterLabel" style="width: 420px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="helpCenterLabel">
            <i class="fas fa-book me-2" style="color: #34d3a3;"></i>
            {{ __('مركز المساعدة') }}
        </h5>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted">F1</small>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('إغلاق') }}"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0" x-data="massarHelpOffcanvas()">

        {{-- Search --}}
        <div class="p-3 border-bottom">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control"
                       placeholder="{{ __('helpcenter::helpcenter.search_placeholder') }}"
                       x-model="query"
                       @input.debounce.400ms="search()"
                       @keydown.escape="results = []; query = ''">
            </div>
        </div>

        {{-- Search Results --}}
        <div x-show="results.length > 0" class="border-bottom">
            <template x-for="item in results" :key="item.id">
                <a :href="item.url" class="d-flex align-items-center px-3 py-2 text-decoration-none border-bottom help-offcanvas-item">
                    <i class="fas fa-file-alt me-2 text-muted small"></i>
                    <div>
                        <div x-text="item.title" class="small fw-semibold text-dark"></div>
                        <small x-text="item.category" class="text-muted" style="font-size:.75rem;"></small>
                    </div>
                </a>
            </template>
        </div>

        {{-- Default: Link to full Help Center --}}
        <div x-show="results.length === 0" class="p-3">
            <a href="#" class="btn btn-outline-secondary btn-sm w-100 mb-3">
                <i class="fas fa-book me-1"></i>{{ __('helpcenter::helpcenter.title') }}
            </a>
        </div>

    </div>
</div>

<script>
(function() {
    var massarHelpCenterInstance = null;

    window.massarHelpCenter = {
        open: function() {
            var el = document.getElementById('helpCenterOffcanvas');
            if (!el) { return; }
            if (!massarHelpCenterInstance) {
                massarHelpCenterInstance = new bootstrap.Offcanvas(el);
            }
            massarHelpCenterInstance.show();
        }
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'F1') {
            e.preventDefault();
            window.massarHelpCenter.open();
        }
    });
})();

function massarHelpOffcanvas() {
    return {
        query: '',
        results: [],
        search() {
            if (this.query.length < 2) { this.results = []; return; }
            fetch(`/help/search?q=${encodeURIComponent(this.query)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => { this.results = data; });
        }
    };
}
</script>
