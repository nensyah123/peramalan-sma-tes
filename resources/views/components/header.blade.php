<!-- =============================== -->
<!-- TOPBAR / HEADER NAVBAR -->
<!-- =============================== -->
<nav class="navbar navbar-expand navbar-light topbar custom-topbar">

    <!-- Judul Sistem -->
    <div class="topbar-title">
        <span class="system-title">
            Sistem Prediksi Permintaan Armada
        </span>

        <span class="system-subtitle">
            CV Mitra Sempurna Jaya Trans
        </span>
    </div>

    <!-- Menu kanan -->
    <ul class="navbar-nav ml-auto align-items-center">

        <!-- Garis pembatas -->
        <div class="topbar-divider"></div>

        <!-- User dropdown -->
        <li class="nav-item dropdown no-arrow">

            <a class="nav-link dropdown-toggle user-area"
               href="#"
               id="userDropdown"
               role="button"
               data-toggle="dropdown">

                <span class="username">
                    {{ Auth::user()->name ?? 'User' }}
                </span>

                <img class="user-avatar"
                     src="{{ url('/template/img/profile.png') }}">
            </a>

            <!-- Dropdown menu -->
            <div class="dropdown-menu dropdown-menu-right shadow user-dropdown">

                <a class="dropdown-item logout-btn"
                   href="#"
                   data-toggle="modal"
                   data-target="#logoutModal">

                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </a>

            </div>
        </li>

    </ul>

</nav>
