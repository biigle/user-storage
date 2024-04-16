@extends('app')

@section('title', 'Create storage request')

@push('scripts')
    <script src="{{ cachebust_asset('vendor/user-storage/scripts/main.js') }}"></script>
    <script type="text/javascript">
        biigle.$declare('user-storage.previousRequest', {!! $previousRequest ?? 'null' !!});
        biigle.$declare('user-storage.availableQuota', {!! $availableQuota !!});
        biigle.$declare('user-storage.maxFilesize', {!! $maxFilesize !!});
        biigle.$declare('user-storage.chunkSize', {!! $chunkSize !!});
    </script>
@endpush

@push('styles')
    <link href="{{ cachebust_asset('vendor/user-storage/styles/main.css') }}" rel="stylesheet">
@endpush

@section('content')
<div id="create-storage-request-container" class="container">
   <div class="col-sm-8 col-sm-offset-2 col-lg-6 col-lg-offset-3">
      <h2>
        New storage request<br>
        <small>{{size_for_humans($usedQuota)}} of {{size_for_humans($availableQuota)}} used ({{round($usedQuota / $availableQuota * 100)}}%)</small>
      </h2>
      <p>
          Add directories and files below. Then submit the storage request to upload the files for review by the instance administrators.
      </p>
      <div class="create-storage-request">
          @if ($previousRequest && $previousRequest->files_count > 0)
            <div v-if="!finished && !finishIncomplete" class="panel panel-info">
                <div class="panel-body text-info">
                    Some files were initialized from an incomplete upload.
                    <form class="form-inline pull-right" action="{{url("api/v1/storage-requests/{$previousRequest->id}")}}" method="POST">
                        <button
                            type="submit"
                            class="btn btn-default btn-xs"
                            title="Delete all files of the previous upload"
                            v-bind:disabled="loading"
                            >
                            Discard all files
                        </button>
                        <input type="hidden" name="_redirect" value="{{ route('create-storage-requests') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                    </form>
                </div>
            </div>
          @endif
        <input
            ref="fileInput"
            class="hidden"
            type="file"
            multiple
            accept="{{$allowedMimeTypes}}"
            v-on:input="handleFilesChosen"
            >

        <div v-if="!finished && !finishIncomplete" class="create-storage-request-buttons clearfix">
            <div v-cloak v-if="loading" class="text-info">
                <loader v-bind:active="true"></loader>
                Uploaded <span v-text="uploadedSizeForHumans"></span> of <span v-text="totalSizeToUploadForHumans"></span>
                (<span v-text="uploadedPercent"></span>%).
            </div>
            <div v-else>

                <button
                    class="btn btn-default"
                    title="Add a new root directory"
                    v-on:click="addRootDirectory"
                    >
                    <i class="fa fa-folder"></i> Add directory
                </button>

                <button
                    v-cloak
                    v-if="hasSelectedDirectory"
                    class="btn btn-default"
                    title="Add a new subdirectory"
                    v-on:click="addDirectory"
                    >
                    <i class="fa fa-folder"></i> Add subdirectory
                </button>
                <button
                    v-else
                    class="btn btn-default"
                    title="Please create or select a directory to add a subdirectory to"
                    disabled
                    >
                    <i class="fa fa-folder"></i> Add subdirectory
                </button>
                <button
                    v-cloak
                    v-if="hasSelectedDirectory"
                    class="btn btn-default"
                    title="Add new files"
                    v-on:click="addFiles"
                    >
                    <i class="fa fa-file"></i> Add files
                </button>
                <button
                    v-else
                    class="btn btn-default"
                    title="Please create or select a directory to add files to"
                    disabled
                    >
                    <i class="fa fa-file"></i> Add files
                </button>

                <span class="pull-right">
                    <button
                        v-cloak
                        v-if="hasFiles"
                        title="Submit the storage request and upload the files"
                        class="btn btn-success"
                        v-on:click="handleSubmit(false)"
                        v-bind:disabled="exceedsMaxSize"
                        >
                        <i class="fa fa-upload"></i> Submit
                    </button>
                    <button
                        v-else
                        class="btn btn-success"
                        title="Add files to submit in this storage request"
                        disabled
                        >
                        <i class="fa fa-upload"></i> Submit
                    </button>
                </span>
            </div>
        </div>


        <div class="panel panel-warning" v-cloak v-if="finishIncomplete">
            <div class="panel-body text-warning">
                <p>
                Some file uploads failed.
                <button class="btn btn-success" title="Reupload failed files" v-on:click="handleSubmit(true)">
                    <i class="fa fa-upload"></i> Retry failed files
                </button>
                <a class="btn btn-default btn" title="Skip failed uploads" v-bind:disabled="loading"
                    href={{ URL::previous() }}>
                    <i class="fa fa-arrow-left"></i> Skip failed files
                </a>
                </p>
            </div>
        </div>

        <p v-cloak v-if="exceedsMaxSize" class="text-danger">
            You have selected more than the <span v-text="availableQuota"></span> of storage available to you.
        </p>

        <p v-cloak v-if="exceedsMaxFilesize" class="text-danger">
            Files larger than the maximum allowed size of <span v-text="maxFilesize"></span> have been ignored.
        </p>

        <p v-cloak v-if="finished" class="text-success">
            The storage request has been submitted. You will be notified when it has been reviewed.
        </p>
        <p v-cloak v-if="!finished && hasFiles" class="text-muted">
            Selected files with a total size of <span v-text="totalSizeForHumans"></span>.
        </p>

        <p v-cloak v-if="pathContainsSpaces" class="text-warning">
            Spaces in the file and directory names were replaced by underscores.
        </p>

        <file-browser
            v-bind:root-directory="rootDirectory"
            v-bind:editable="editable"
            v-bind:selectable="true"
            v-on:select="selectDirectory"
            v-on:unselect="unselectDirectory"
            v-on:remove-directory="removeDirectory"
            v-on:remove-file="removeFile"
            ></file-browser>
            
      </div>
    </div>
</div>
@endsection
