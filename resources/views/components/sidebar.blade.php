<ul class="navbar-nav sidebar accordion" id="accordionSidebar">

    <!-- Logo -->
    <div class="sidebar-brand-area">
        <img src="{{ url('/template/img/logorental.png') }}" alt="MSJ Trans">
    </div>

    <!-- Dashboard -->
    <div class="sidebar-section-label">Main</div>

    <li class="nav-item {{ Request::is('/') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Management -->
    <div class="sidebar-section-label">Management</div>

    <li class="nav-item {{ Request::is('management-kendaraan*') || Request::is('input-data*') ? 'active' : '' }}">
        <a class="nav-link {{ Request::is('management-kendaraan*') || Request::is('input-data*') ? '' : 'collapsed' }}"
           href="#"
           data-toggle="collapse"
           data-target="#collapseManagement">
            <i class="fas fa-fw fa-folder"></i>
            <span>Management Data</span>
        </a>

        <div id="collapseManagement"
             class="collapse {{ Request::is('management-kendaraan*') || Request::is('input-data*') ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="collapse-inner">
                <a class="collapse-item {{ Request::is('management-kendaraan*') ? 'active' : '' }}"
                   href="{{ url('/management-kendaraan') }}">
                    <i class="fas fa-fw fa-car"></i> Daftar Kendaraan
                </a>
                <a class="collapse-item {{ Request::is('input-data*') ? 'active' : '' }}"
                   href="{{ url('/input-data') }}">
                    <i class="fas fa-fw fa-list"></i> Daftar Transaksi
                </a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">

    <!-- Peramalan -->
    <div class="sidebar-section-label">Peramalan</div>

    <li class="nav-item {{ Request::is('peramalan-tes*') ? 'active' : '' }}">
        <a class="nav-link {{ Request::is('peramalan-tes*') ? '' : 'collapsed' }}"
           href="#"
           data-toggle="collapse"
           data-target="#collapsePeramalan">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Metode Peramalan</span>
        </a>

        <div id="collapsePeramalan"
             class="collapse {{ Request::is('peramalan-tes*') ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="collapse-inner">
                <a class="collapse-item {{ Request::is('peramalan-tes*') ? 'active' : '' }}"
                   href="{{ url('/peramalan-tes') }}">
                    <i class="fas fa-fw fa-wave-square"></i> Triple Exp. Smoothing
                </a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">

    <!-- Hasil -->
    <div class="sidebar-section-label">Hasil</div>

    <li class="nav-item {{ Request::is('analisis-armada*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/analisis-armada') }}">
            <i class="fas fa-fw fa-chart-bar"></i>
            <span>Analisis Armada</span>
        </a>
    </li>

</ul>
