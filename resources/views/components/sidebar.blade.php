<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <img src="{{ url('/template/img/logorental.png') }}"
             alt="Logo MSJ Trans"
             class="sidebar-logo">
    </div>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item {{ Request::is('/') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Management</div>

    <!-- Management Collapse -->
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
                    <i class="fas fa-fw fa-car mr-2"></i>
                    Daftar Kendaraan
                </a>

                <a class="collapse-item {{ Request::is('input-data*') ? 'active' : '' }}"
                   href="{{ url('/input-data') }}">
                    <i class="fas fa-fw fa-list mr-2"></i>
                    Daftar Transaksi
                </a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Peramalan</div>

    <!-- Peramalan -->
    <li class="nav-item {{ Request::is('peramalan-smp*') || Request::is('peramalan-tes*') ? 'active' : '' }}">

        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#collapsePeramalan">

            <i class="fas fa-fw fa-chart-line"></i>
            <span>Metode Peramalan</span>
        </a>

        <div id="collapsePeramalan"
             class="collapse {{ Request::is('peramalan-smp*') || Request::is('peramalan-tes*') ? 'show' : '' }}"
             data-parent="#accordionSidebar">

            <div class="collapse-inner">

                {{-- <a class="collapse-item {{ Request::is('peramalan-smp*') ? 'active' : '' }}"
                   href="{{ url('/peramalan-smp') }}">
                    Simple Moving Average
                </a> --}}

                <a class="collapse-item {{ Request::is('peramalan-tes*') ? 'active' : '' }}"
                   href="{{ url('/peramalan-tes') }}">
                    Triple Exponential Smoothing
                </a>

            </div>
        </div>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Hasil</div>

    <li class="nav-item {{ Request::is('perbandingan*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/perbandingan') }}">
            <i class="fas fa-fw fa-history"></i>
            <span>Riwayat Peramalan</span>
        </a>
    </li>

    <li class="nav-item {{ Request::is('analisis-armada*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/analisis-armada') }}">
            <i class="fas fa-fw fa-chart-bar"></i>
            <span>Analisis Armada</span>
        </a>
    </li>

</ul>
