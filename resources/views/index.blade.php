@extends('layout.master')
@section('Title', 'Home')


    @section('Content')

        @include('component.msg')

        <section class="mt-5 pt-3 header">
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
            <div class="container">

                <div class="d-flex align-items-center file-shown">
                    {{-- File Details --}}
                    <div class="file-details w-25" id="fileDetails">
                        <h3>File Details</h3>
                        <p><strong>File Name:</strong> <span id="fileName"></span> </p>
                        <p><strong>File Size:</strong> <span id="fileSize"></span> </p>
                        <p><strong>File Extension:</strong> <span id="fileExtension"></span> </p>
                    </div>

                    {{-- File Preview --}}
                    <div class="file-preview text-center w-75" id="filePreview">

                    </div>
                </div>

                {{-- File Encryption and Decryption --}}
                <div class="file-encryption mt-5 pt-4">
                    <div class="buttons d-flex justify-content-center gap-2 pb-4 mb-3">
                        <button class="btn" id="encryptBtn">Encrypt</button>
                        <button class="btn" id="decryptBtn">Decrypt</button>
                    </div>
                    <div id="encryptedContent" class="text-center" style="display: none;">
                        <h2 class="text-center">Encrypted Content</h2>
                        <div class="d-flex flex-direction-column">
                            <textarea id="encryptedContentTextarea" name="encryptedContent" rows="10" cols="50" readonly></textarea>
                            <button class="btn mt-2" id="saveEncryptedBtn">Save Encrypted File</button>
                        </div>
                    </div>
                </div>

            </div>
        </section>

    @endsection

@section('Js')

    <script>
        //Initiallization of the file input
        $(function () {
            bsCustomFileInput.init();
        });
    </script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // defining routes in order to use in the main.js as a varaiable
        var encryptRoute = "{{ route('file.encrypt') }}";
        var decryptRoute = "{{ route('file.decrypt') }}";
        var detailsRoute = "{{ route('file.details') }}";
    </script>

@stop
