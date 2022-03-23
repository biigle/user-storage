@can('create', \Biigle\Modules\UserStorage\StorageRequest::class)
    <a href="{{route('create-storage-requests')}}" class="btn btn-default" title="Upload files">
        <i class="fa fa-upload"></i> Upload Files
    </a>
@else
    <button class="btn btn-default" title="Guests are not allowed to upload files" disabled>
        <i class="fa fa-upload"></i> Upload Files
    </button>
@endcan
