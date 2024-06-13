<?php

namespace Biigle\Modules\UserStorage\Support;

use Biigle\Modules\UserStorage\StorageRequestFile;
use Storage;

/**
 * When invoked repeadetly, subsequently returns the content of storage request file
 * chunks. This is meant to be used together with a \GuzzleHttp\Psr7\PumpStream.
 */
class ChunkedFileStreamPump
{
    /**
     * Index of the current chunk to return for the stream.
     */
    public int $currentChunk;

    /**
     * Storage disk to read chunks from.
     */
    public $disk;

    /**
     * Base path of the storage request file.
     */
    public string $path;

    public $buffer;

    public function __construct(public StorageRequestFile $file)
    {
        $this->currentChunk = 0;
        $this->disk = Storage::disk(config('user_storage.pending_disk'));
        $this->path = $this->file->request->getPendingPath($this->file->path);
        $this->buffer = fopen('php://temp', 'r+');
    }

    public function __destruct()
    {
        fclose($this->buffer);
    }

    public function __invoke($length)
    {
        $data = fread($this->buffer, $length);

        if ($data === '') {
            if ($this->currentChunk >= $this->file->total_chunks) {
                return false;
            }

            // Recreate the buffer in case the chunks have uneven sizes. A larger previous
            // chunk could leave trailing data in the buffer otherwise. The last chunk is
            // often smaller than the previous chunks.
            fclose($this->buffer);
            $this->buffer = fopen('php://temp', 'r+');

            $chunkPath = "{$this->path}.{$this->currentChunk}";
            $this->currentChunk += 1;

            stream_copy_to_stream($this->disk->readStream($chunkPath), $this->buffer);
            rewind($this->buffer);

            return $this($length);
        }

        return $data;
    }
}
