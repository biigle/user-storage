<?php
$storageUser = Biigle\Modules\UserStorage\User::convert($shownUser);
?>

<div class="col-xs-6">
    <div class="panel panel-default">
        <div class="panel-heading">
            Created {{Biigle\Modules\UserStorage\StorageRequest::where('user_id', $shownUser->id)->count()}} storage request(s). Quota:
        </div>
        <div class="panel-body text-center">
            {!!size_for_humans($storageUser->storage_quota_used)!!} / {!!size_for_humans($storageUser->storage_quota_available)!!} ({{round($storageUser->storage_quota_used / $storageUser->storage_quota_available * 100, 2)}}%)
        </div>
    </div>
</div>
