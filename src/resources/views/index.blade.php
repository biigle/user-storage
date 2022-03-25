@extends('app')

@section('title', 'Your storage requests')

@push('scripts')
    <script src="{{ cachebust_asset('vendor/user-storage/scripts/main.js') }}"></script>
    <script type="text/javascript">
      biigle.$declare('user-storage.requests', {!! $requests !!});
      biigle.$declare('user-storage.expireDate', '{!! $expireDate->toJson() !!}');
      biigle.$declare('user-storage.usedQuota', {!! $usedQuota !!});
      biigle.$declare('user-storage.availableQuota', {!! $availableQuota !!});
   </script>
@endpush

@push('styles')
    <link href="{{ cachebust_asset('vendor/user-storage/styles/main.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container" id="index-storage-request-container">
   <div class="col-sm-8 col-sm-offset-2 col-lg-6 col-lg-offset-3">
        <h2 class="user-storage-title">
            <loader
                v-cloak
                v-bind:active="loading"
                ></loader>
            Your storage requests<br>
            <small v-cloak>
                <span v-text="usedQuota"></span> of <span v-text="availableQuota"></span> used (<span v-text="usedQuotaPercent"></span>%)
            </small>
        </h2>
        <request-list
            v-cloak
            v-bind:requests="requests"
            v-bind:expire-date="expireDate"
            v-bind:selected-request="selectedRequest"
            v-on:select="handleSelectRequest"
            v-on:delete="handleDeleteRequest"
            v-on:extend="handleExtendRequest"
            ></request-list>

        <file-browser
            v-cloak
            v-if="hasSelectedRequest"
            v-bind:root-directory="selectedRequestRoot"
            v-bind:editable="true"
            v-on:remove-directory="removeDirectory"
            v-on:remove-file="removeFile"
            ></file-browser>

        @if (count($requests) > 0)
            <p class="text-muted">
                Need more storage space? <a href="mailto:{{config('biigle.admin_email')}}">Get in touch</a>.
            </p>
        @endif
    </div>
</div>
@endsection
