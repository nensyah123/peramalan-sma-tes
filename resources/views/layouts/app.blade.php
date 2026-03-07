<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Forecasting Nensyah</title>
    <link rel="icon" href="{{ url('/template/img/logorental.png') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom fonts for this template-->
    <link href="{{ asset('template/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ asset('template/css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('template/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- ============================================
         FIX SIDEBAR — letakkan SETELAH sb-admin-2
         ============================================ -->
    <style>
        /* --- Fix teks submenu terpotong --- */
        .sidebar .nav-item .collapse .collapse-inner .collapse-item,
        .sidebar .nav-item .collapsing .collapse-inner .collapse-item {
            white-space: normal !important;
            line-height: 1.3;
        }

        @media (min-width: 768px) {
            .sidebar .nav-item .collapse,
            .sidebar .nav-item .collapsing {
                min-width: 200px;
            }
        }

        /* --- FIX 1: Logo border sejajar header --- */
        .sidebar-header {
            background: #ffffff !important;
            height: 75px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-bottom: 1px solid #e5e5e5 !important;
        }

        /* --- FIX 2: Submenu selalu terlihat tanpa diklik --- */

        /* Background putih agar kontras */
        .sidebar .nav-item .collapse-inner,
        .sidebar .nav-item .collapsing .collapse-inner {
            background: #ffffff !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            margin: 0 10px 6px 10px !important;
        }

        /* Teks submenu gelap & selalu visible */
        .sidebar .nav-item .collapse-inner .collapse-item {
            color: #333333 !important;
            font-size: 14px !important;
            padding: 11px 18px !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            text-decoration: none !important;
            background: transparent !important;
        }

        /* Icon submenu */
        .sidebar .nav-item .collapse-inner .collapse-item i {
            color: #555555 !important;
        }

        /* Hover submenu */
        .sidebar .nav-item .collapse-inner .collapse-item:hover {
            background-color: #e4e4e4 !important;
            color: #000000 !important;
            font-weight: 600 !important;
        }

        /* Active submenu */
        .sidebar .nav-item .collapse-inner .collapse-item.active {
            background-color: #dcdcdc !important;
            color: #000000 !important;
            font-weight: 700 !important;
        }
    </style>

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                @include('components.header')

                <!-- Begin Page Content -->
                <div class="container-fluid">
                  @yield('content')
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            @include('components.footer')
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('template/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('template/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('template/vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('template/js/sb-admin-2.min.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('template/vendor/chart.js/Chart.min.js') }}"></script>

    <script src="{{ asset('template/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('template/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

    @stack('scripts')
  </body>

</html>
