
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
                Copyright@ {{ \Carbon\Carbon::now()->year }} Softlock Task
            </div>
        </div>
    </footer>

    <script src="{{ asset('asset/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('asset/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- File Input -->
    <script src="{{ asset('asset/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
    <!-- Main JS -->
    <script src="{{ asset('asset/js/main.js') }}"></script>

