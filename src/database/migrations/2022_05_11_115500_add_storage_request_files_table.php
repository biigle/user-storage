<?php

use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('storage_request_files', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('storage_request_id');
            $table->foreign('storage_request_id')
                  ->references('id')
                  ->on('storage_requests')
                  ->onDelete('cascade');

            $table->string('path', 512);
            $table->unsignedBigInteger('size');
            $table->json('received_chunks')->nullable();
            $table->unsignedInteger('total_chunks')->nullable();

            $table->index('storage_request_id');
            $table->unique(['path', 'storage_request_id']);
        });

        $storageDisk = Storage::disk(config('user_storage.storage_disk'));
        $pendingDisk = Storage::disk(config('user_storage.pending_disk'));

        StorageRequest::eachById(function ($request) use ($storageDisk, $pendingDisk) {
            $files = array_filter(explode(',', $request->files ?? ''));

            if (is_null($request->expires_at)) {
                $disk = $pendingDisk;
                $prefix = $request->getPendingPath();
            } else {
                $disk =  $storageDisk;
                $prefix = $request->getStoragePath();
            }

            $create = array_map(function ($path) use ($disk, $prefix) {
                return [
                    'path' => $path,
                    'size' => $disk->fileSize("{$prefix}/{$path}"),
                ];
            }, $files);

            $request->files()->createMany($create);
        });

        Schema::table('storage_requests', function (Blueprint $table) {
            $table->dropColumn('files');
        });

        User::whereNotNull('attrs->storage_quota_used')->eachById(function ($user) {
            $attrs = $user->attrs;
            unset($attrs['storage_quota_used']);
            $user->attrs = $attrs;
            $user->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('storage_requests', function (Blueprint $table) {
            $table->text('files')->default('');
        });

        DB::table('storage_request_files')
            ->select('storage_request_id', 'path')
            ->get()
            ->groupBy('storage_request_id')
            ->each(function ($files, $requestId) {
                $files = $files->pluck('path')->join(',');
                StorageRequest::where('id', $requestId)->update(['files' => $files]);
            });

        $sizes = DB::table('storage_request_files')
            ->join('storage_requests', 'storage_requests.id', '=', 'storage_request_files.storage_request_id')
            ->select('storage_requests.user_id', DB::raw('sum(storage_request_files.size) as size'))
            ->groupBy('user_id')
            ->get()
            ->each(function ($item) {
                $user = User::find($item->user_id);
                $attrs = $user->attrs;
                $attrs['storage_quota_used'] = intval($item->size);
                $user->attrs = $attrs;
                $user->save();
            });

        Schema::drop('storage_request_files');
    }
};
