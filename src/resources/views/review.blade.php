@extends('app')

@section('title', 'Review request')

@push('scripts')
    <script src="{{ cachebust_asset('vendor/user-storage/scripts/main.js') }}"></script>
    <script type="text/javascript">
      biigle.$declare('user-storage.request', {!! $request !!});
   </script>
@endpush

@push('styles')
    <link href="{{ cachebust_asset('vendor/user-storage/styles/main.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container" id="review-storage-request-container">
   <div class="col-sm-8 col-sm-offset-2 col-lg-6 col-lg-offset-3">
        <h2 class="user-storage-title">
            Review storage request<br>
            <small>by <a href="{{route('admin-users-show', $request->user->id)}}">{{$request->user->firstname}} {{$request->user->lastname}} ({{$request->user->affiliation ?: 'no affiliation'}})</a></small>
        </h2>
        <file-browser
            v-cloak
            v-bind:root-directory="requestRoot"
            ></file-browser>

        <div v-cloak v-if="finished">
            <p v-if="approved" class="text-success">
                The storage request has been approved.
            </p>
            <p v-if="rejected" class="text-danger">
                The storage request has been rejected. All files will be deleted.
            </p>
        </div>
        <div v-else>
            <div v-cloak v-if="rejecting" class="clearfix">
                <form v-on:submit.prevent="handleReject">
                    <div class="form-group">
                        <textarea class="form-control" v-model="rejectReason" placeholder="Reason for rejection" required></textarea>
                    </div>
                    <button
                        class="btn btn-danger pull-right"
                        title="Reject the request and delete all files"
                        type="submit"
                        v-bind:disabled="cannotReject"
                        >
                        <loader v-cloak v-bind:active="loading"></loader>
                        Reject
                    </button>

                    <button
                        class="btn btn-default"
                        title="Cancel rejecting the request"
                        type="button"
                        v-bind:disabled="loading"
                        v-on:click="handleCancelReject"
                        >
                        Cancel
                    </button>
                </form>
            </div>
            <div v-else class="clearfix">
                <button
                    class="btn btn-success pull-right"
                    title="Approve the request"
                    v-bind:disabled="loading"
                    v-on:click="handleApprove"
                    >
                    <loader v-cloak v-bind:active="loading"></loader>
                    Approve
                </button>

                <button
                    class="btn btn-default"
                    title="Reject the request"
                    v-bind:disabled="loading"
                    v-on:click="handleRejecting"
                    >
                    Reject
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
