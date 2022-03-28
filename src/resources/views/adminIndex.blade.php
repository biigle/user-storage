<?php
if (!function_exists('size_for_humans')) {
    function size_for_humans($size) {
        $size = intval($size);
        $unit = '';
        $units = ['kB', 'MB', 'GB', 'TB'];
        do {
            $size /= 1000;
            $unit = array_shift($units);
        } while ($size > 1000 && count($units) > 0);

        return round($size, 2).'&nbsp;'.$unit;
    }
}
?>

<div class="col-sm-6">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                User storage
                <span class="pull-right" title="Number of storage requests">{{ Biigle\Modules\UserStorage\StorageRequest::count() }}</span>
            </h3>
        </div>
        <div class="panel-body">
            <p class="h1 text-center">{!!size_for_humans(Biigle\User::sum(DB::raw('cast("attrs"->>\'storage_quota_used\' as integer)')))!!}</p>
        </div>
    </div>
</div>
