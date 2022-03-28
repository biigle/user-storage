<?php

namespace Biigle\Modules\UserStorage\Support;

use Illuminate\Filesystem\FilesystemManager as BaseManager;

class FilesystemManager extends BaseManager
{
    /**
     * Resolve the given disk.
     *
     * @param  string  $name
     * @param  array|null  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name, $config = null)
    {
        if (preg_match('/^user-[0-9]+$/', $name) === 1) {
            $config = $this->getConfig(config('user_storage.storage_disk'));
            if (array_key_exists('root', $config)) {
                $config['root'] .= '/'.$name;
            } else {
                $config['root'] = $name;
            }

            if (array_key_exists('url', $config)) {
                $config['url'] .= '/'.$name;
            }
        }

        return parent::resolve($name, $config);
    }
}
