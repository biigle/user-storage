@can('create', \Biigle\Modules\UserStorage\StorageRequest::class)
    <a href="{{route('create-storage-requests')}}" class="btn btn-default" title="Upload files in a new storage request">
        <i class="fa fa-upload"></i> Upload Files
    </a>
@else
    <button class="btn btn-default" title="You cannot upload files right now" disabled>
        <i class="fa fa-upload"></i> Upload Files
    </button>
@endcan
