@extends('app')

@section('title', 'Create storage request')

@push('scripts')
    <script src="{{ cachebust_asset('vendor/user-storage/scripts/main.js') }}"></script>
@endpush

@push('styles')
    <link href="{{ cachebust_asset('vendor/user-storage/styles/main.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
   <div class="col-sm-8 col-sm-offset-2 col-lg-6 col-lg-offset-3">
      <h2>New storage request</h2>
      <p>
          Add directories and files below. Then submit the storage request to upload the files for review by the instance administrators.
      </p>
      <div id="create-storage-request-container">
        <file-uploader
            accept="{{$allowedMimeTypes}}"
            v-bind:max-size="{{$maxSize}}"
            ></file-uploader>
      </div>
    </div>
</div>
@endsection
