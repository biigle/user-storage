<?php
$storageUser = Biigle\Modules\UserStorage\User::convert($shownUser);
?>

<div class="col-xs-6">
    <div id="storage-quota-panel" class="panel panel-default">
        <div class="panel-heading">
            <button class="btn btn-default btn-xs pull-right" title="Edit quota" v-on:click="toggleEditing" v-bind:class="classObj"><i class="fa fa-pen"></i></button>
            Created {{Biigle\Modules\UserStorage\StorageRequest::where('user_id', $shownUser->id)->count()}} storage request(s).
        </div>
        <div class="panel-body text-center">
            <span v-show="!editing">
                Quota: {!!size_for_humans($storageUser->storage_quota_used)!!} / {!!size_for_humans($storageUser->storage_quota_available)!!} ({{round($storageUser->storage_quota_used / $storageUser->storage_quota_available * 100, 2)}}%)
            </span>
            <form v-cloak class="form-inline" v-if="editing" action="{{url("api/v1/users/{$shownUser->id}/storage-request-quota")}}" method="POST">
                <div class="form-group">
                    <div class="input-group">
                        <input name="quota" type="number" class="form-control" value="{{$storageUser->storage_quota_available}}" min="0" step="1" list="defaultQuota">
                        <span class="input-group-addon">bytes</span>
                    </div>
                </div>
                <datalist id="defaultQuota">
                  <option value="{{config('user_storage.user_quota')}}" label="{{size_for_humans(config('user_storage.user_quota'))}}"></option>
                </datalist>
                <button type="submit" class="btn btn-success">Update</button>
                @csrf
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
biigle.$mount('storage-quota-panel', {
    data: {
        editing: false,
    },
    computed: {
        classObj() {
            return this.editing ? 'active' : '';
        },
    },
    methods: {
        toggleEditing() {
            this.editing = !this.editing;
        },
    },
});
</script>
@endpush
