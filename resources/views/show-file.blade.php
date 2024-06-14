@extends('layout.master')
@section('Title', 'File')


@section('Content')

    <section class="mt-5 pt-4 mb-5 pb-5">
        <div class="container d-flex align-items-center">
            <div class="file-details w-25">
                <h3>File Details</h3>
                <p><strong>File Name:</strong> {{ $fileDetails['name'] }}</p>
                <p><strong>File Size:</strong> {{ number_format($fileDetails['size'] / 1024, 2) }} KB</p>
                <p><strong>File Extension:</strong> {{ $fileDetails['extension'] }}</p>
            </div>
            <div class="file-preview text-center w-75">
                @if($fileDetails['type'] == 'image')
                    <img src="{{ Storage::url($fileDetails['path']) }}" alt="{{ $fileDetails['name'] }}" style="max-width: 100%; max-height: 400px;">
                @elseif($fileDetails['type'] == 'pdf')
                    <iframe src="{{ Storage::url($fileDetails['path']) }}" width="100%" height="500px"></iframe>
                @elseif($fileDetails['type'] == 'text')
                    <pre>{{ Storage::get($fileDetails['path']) }}</pre>
                @else
                    <p>Preview not available for this file type.</p>
                    <img src="{{ asset('images/file-icon.png') }}" alt="File Icon" width="100">
                @endif
            </div>
        </div>
    </section>


@endsection



@section('Js')

@stop
