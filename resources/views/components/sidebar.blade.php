<ul class="navbar-nav sidebar accordion" id="accordionSidebar">

    <!-- Logo -->
    <div class="sidebar-brand-area">
        <img src="{{ url('/template/img/logorental.png') }}" alt="MSJ Trans">
    </div>

    <!-- MAIN -->
    <div class="sidebar-section-label">Main</div>

    <li class="nav-item {{ Request::is('/') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- ARMADA -->
    <div class="sidebar-section-label">Armada</div>

    <li class="nav-item {{ Request::is('management-kendaraan*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/management-kendaraan') }}">
            <i class="fas fa-fw fa-car"></i>
            <span>Daftar Kendaraan</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- PENYEWAAN -->
    <div class="sidebar-section-label">Penyewaan</div>

    <li class="nav-item {{ Request::is('transaksi-penyewaan*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/transaksi-penyewaan') }}">
            <i class="fas fa-fw fa-exchange-alt"></i>
            <span>Transaksi Penyewaan</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- PERAMALAN -->
    <div class="sidebar-section-label">Peramalan</div>

    <li class="nav-item {{ Request::is('peramalan-tes') && !Request::is('peramalan-tes/riwayat') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/peramalan-tes') }}">
            <i class="fas fa-fw fa-wave-square"></i>
            <span>Triple Exp. Smoothing</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- RIWAYAT -->
    <div class="sidebar-section-label">Riwayat</div>

    <li class="nav-item {{ Request::is('peramalan-tes/riwayat') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('/peramalan-tes/riwayat') }}">
            <i class="fas fa-fw fa-history"></i>
            <span>Riwayat Peramalan</span>
        </a>
    </li>

</ul>
