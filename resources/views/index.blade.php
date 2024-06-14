@extends('layout.master')
@section('Title', 'Home')


    @section('Content')

        @include('component.msg')
        <section class="mt-5 pt-3">
            <h1 class="text-center">Upolad File</h1>
        </section>

        <section>
            <div class="container">
                <div class="form-group pt-4 w-50 m-auto">
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="fileInput" required>
                            <label class="custom-file-label" for="fileInput">Choose file</label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-5 pt-4 mb-5 pb-5" id="detailsSection" style="display: none;">
            <div class="container d-flex align-items-center">
                <div class="file-details w-25" id="fileDetails">
                    <h3>File Details</h3>
                    <p><strong>File Name:</strong> <span id="fileName"></span> </p>
                    <p><strong>File Size:</strong> <span id="fileSize"></span> </p>
                    <p><strong>File Extension:</strong> <span id="fileExtension"></span> </p>
                </div>
                <div class="file-preview text-center w-75" id="filePreview">

                </div>
            </div>
        </section>

    @endsection

@section('Js')

    <script>
        $(function () {
            bsCustomFileInput.init();
        });
    </script>

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#fileInput').on('change', function() {
                var file = this.files[0];
                var formData = new FormData();
                formData.append('file', file);
                console.log('File:', formData.get('file'));
                $.ajax({
                    url: '{{ route("file.details") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        $('#fileName').text(data.name);
                        $('#fileSize').text(data.size);
                        $('#fileExtension').text(data.extension);
                        $('#filePreview').html(data.preview);
                        $('#detailsSection').show();
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var errorMessagesDiv = document.getElementById('errorMessages');

            if (errorMessagesDiv) {
                setTimeout(function () {
                    errorMessagesDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>

@stop
