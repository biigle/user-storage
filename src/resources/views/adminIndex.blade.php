<div class="col-sm-6">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                User storage
                <span class="pull-right" title="Number of storage requests">{{ Biigle\Modules\UserStorage\StorageRequest::count() }}</span>
            </h3>
        </div>
        <div class="panel-body">
            <p class="h1 text-center">{!!size_for_humans(Biigle\Modules\UserStorage\StorageRequestFile::sum('size'))!!}</p>
        </div>
    </div>
</div>
