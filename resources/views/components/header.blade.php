<style>
/* ===================================================
   TOPBAR / HEADER — MSJ TRANS
   =================================================== */
.custom-topbar {
    background: #ffffff !important;
    border-bottom: 1px solid #f0f0f0;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
    padding: 0 24px;
    height: 64px;
    z-index: 100;
}

/* ===== JUDUL SISTEM ===== */
.topbar-brand {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.2;
}
.topbar-brand .system-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1a1a2e;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.topbar-brand .system-subtitle {
    font-size: 0.72rem;
    color: #888;
    font-weight: 400;
}

/* ===== BREADCRUMB / PAGE INDICATOR ===== */
.topbar-page-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 24px;
    padding-left: 24px;
    border-left: 1px solid #eee;
}
.topbar-page-info .page-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #be4132;
    flex-shrink: 0;
}
.topbar-page-info .page-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #444;
}

/* ===== DIVIDER ===== */
.topbar-divider {
    width: 1px;
    height: 32px;
    background: #eee;
    margin: 0 16px;
}

/* ===== USER AREA ===== */
.user-area {
    display: flex !important;
    align-items: center;
    gap: 10px;
    padding: 6px 4px !important;
    border-radius: 0;
    background: transparent;
    border: none;
    transition: opacity 0.2s ease;
}
.user-area:hover {
    background: transparent !important;
    opacity: 0.8;
    text-decoration: none !important;
}
.user-area .username {
    font-size: 0.83rem;
    font-weight: 600;
    color: #74271f;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.user-area .user-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e0e0e0;
}

/* User dropdown */
.user-dropdown {
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    padding: 8px;
    min-width: 160px;
    margin-top: 8px !important;
}
.user-dropdown .dropdown-item {
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 0.83rem;
    color: #444;
    transition: background 0.15s, color 0.15s;
}
.user-dropdown .logout-btn {
    color: #be4132;
    font-weight: 600;
}
.user-dropdown .logout-btn:hover {
    background: rgba(190,65,50,0.1);
    color: #74271f;
}

/* ===== SIDEBAR TOGGLE BUTTON ===== */
#sidebarToggle, #sidebarToggleTop {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #f5f5f5;
    border: none;
    color: #555;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
    margin-right: 12px;
}
#sidebarToggle:hover, #sidebarToggleTop:hover {
    background: rgba(116,39,31,0.1);
    color: #74271f;
}
</style>

<nav class="navbar navbar-expand navbar-light topbar custom-topbar mb-4">

    <!-- Sidebar Toggle -->
    <button id="sidebarToggle" class="rounded">
        <i class="fas fa-bars fa-sm"></i>
    </button>

    <!-- Judul Sistem -->
    <div class="topbar-brand">
        <span class="system-title">Sistem Prediksi Permintaan Armada</span>
        <span class="system-subtitle">CV Mitra Sempurna Jaya Trans</span>
    </div>

    <!-- Kanan -->
    <ul class="navbar-nav ml-auto align-items-center">

        <div class="topbar-divider"></div>

        <!-- User Dropdown -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle user-area"
               href="#"
               id="userDropdown"
               role="button"
               data-toggle="dropdown"
               aria-haspopup="true"
               aria-expanded="false">

                <span class="username">{{ Auth::user()->name ?? 'Admin' }}</span>
                <img class="user-avatar"
                     src="{{ url('/template/img/profile.png') }}"
                     alt="Avatar"
                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'A') }}&background=74271f&color=fff&size=64'">
            </a>

            <div class="dropdown-menu dropdown-menu-right shadow user-dropdown animated--fade-in"
                 aria-labelledby="userDropdown">

                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="dropdown-item logout-btn" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </form>
            </div>
        </li>

    </ul>
</nav>
