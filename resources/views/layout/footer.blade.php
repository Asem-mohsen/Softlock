
    <footer>
        <div class="container d-flex flex-direction-column gap-2">
            <div>
                <ul class="d-flex justify-content-center align-content-center gap-2">
                    <li>
                        <a href="https://www.linkedin.com/in/assem-m-89a61414b" target="_blanck">
                            <i class="fa-brands fa-linkedin"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Asem-mohsen" target="_blanck">
                            <i class="fa-brands fa-github"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/asem.semsm" target="_blanck">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="text-center text-white border-top border-white pt-2">
                Copyright@ {{ \Carbon\Carbon::now()->toDateString() }} Softlock Task
            </div>
        </div>
    </footer>

    <script src="{{ asset('asset/plugins/jquery/jquery.min.js') }}"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('asset/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('asset/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Sparkline -->
    <script src="{{ asset('asset/plugins/sparklines/sparkline.js') }}"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{ asset('asset/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

    <!-- overlayScrollbars -->
    <script src="{{ asset('asset/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>

    <!-- File Input -->
    <script src="{{ asset('asset/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('asset/js/adminlte.js') }}"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="{{ asset('asset/js/demo.js') }}"></script>
    <!-- Main JS -->
    <script src="{{ asset('asset/js/main.js') }}"></script>

