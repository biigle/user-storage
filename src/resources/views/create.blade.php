@extends('app')

@section('title', 'Create storage request')

@push('scripts')
    <script src="{{ cachebust_asset('vendor/user-storage/scripts/main.js') }}"></script>
    <script type="text/javascript">
        biigle.$declare('user-storage.previousRequest', {!! $previousRequest ?? 'null' !!});
        biigle.$declare('user-storage.availableQuota', {!! $availableQuota !!});
        biigle.$declare('user-storage.maxFilesize', {!! $maxFilesize !!});
        biigle.$declare('user-storage.chunkSize', {!! $chunkSize !!});
        biigle.$declare('user-storage.usedQuota', {!! $usedQuota !!});
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
            <div v-if="!finished" class="panel panel-info">
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

        <div v-cloak v-if="loading" class="text-info">
            <loader v-bind:active="true"></loader>
            Uploaded
            <span v-if="finishIncomplete">
                <span v-text="uploadedSizeForHumans"></span> of <span v-text="totalSizeFailedFilesToUploadForHumans"></span>
                (<span v-text="uploadedPercentFailedFiles"></span>%).

            </span>
            <span v-else>
                <span v-text="uploadedSizeForHumans"></span> of <span v-text="totalSizeToUploadForHumans"></span>
                (<span v-text="uploadedPercent"></span>%).

            </span>
         </div>
        <div v-cloak v-if="!finished && !finishIncomplete && !loading && !ignoreFiles" class="create-storage-request-buttons clearfix">
            <div>
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
                        v-on:click="handleSubmit()"
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
        <div v-cloak v-if="finishIncomplete && !loading" class="panel panel-warning text-center">
            <div class="panel-body text-warning">
                <p><i class="fa fa-exclamation-triangle"> </i></p>
                <p v-if="noFilesUploaded">
                    All file uploads failed.<br>
                    You can cancel or retry the upload.
                </p>
                <p v-else>
                    Some file uploads failed.<br>
                    You can ignore the files and submit the storage request anyway<br>or retry the upload.
                </p>
                <p>
                <a v-if="noFilesUploaded" class="btn btn-default btn" title="Cancel uploads" href="{{URL::previous()}}"> 
                    Cancel
                </a>
                <button v-else class="btn btn-default btn" title="Skip failed uploads" v-on:click="skipFailedFiles"> 
                    Ignore
                </button>

                <button class="btn btn-success" title="Reupload failed files" v-on:click="handleSubmit(true)">
                    Retry
                </button>
                </p>
            </div>
        </div>

        <p v-cloak v-if="exceedsMaxSize" class="text-danger">
            Your upload is larger than the last <span v-text="remainingQuota"></span> of storage available to you.
        </p>

        <p v-cloak v-if="exceedsMaxFilesize" class="text-danger">
            Files larger than the maximum allowed size of <span v-text="maxFilesize"></span> have been ignored.
        </p>

        <p v-cloak v-if="finished && !uploadNotSuccessfull" class="text-success">
            The storage request has been submitted. You will be notified when it has been reviewed.
        </p>

        <p v-cloak v-if="hasDuplicatedFiles" class="text-info">
            Some files <i class="fa fa-info-circle"></i> were skipped during upload. 
            They already exist in another storage request with equal directory name.
        </p>

        <p v-cloak v-if="!finished && hasFiles" class="text-muted">
            <span v-if="finishIncomplete">Failed files with a total size of <span v-text="totalSizeFailedFilesForHumans"></span>.</span> 
            <span v-else>Selected files with a total size of <span v-text="totalSizeForHumans"></span>.</span>
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
