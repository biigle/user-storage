# BIIGLE User Storage Module

[![Test status](https://github.com/biigle/user-storage/workflows/Tests/badge.svg)](https://github.com/biigle/user-storage/actions?query=workflow%3ATests)

This is the BIIGLE module to allow file upload and storage for users.

## Installation

1. Run `composer require biigle/user-storage`.
2. Add `Biigle\Modules\UserStorage\UserStorageServiceProvider::class` to the `providers` array in `config/app.php`.
3. Run `php artisan vendor:publish --tag=public` to refresh the public assets of the modules. Do this for every update of this module.
4. Set the environment variables `USER_STORAGE_STORAGE_DISK` and `USER_STORAGE_PENDING_DISK` to the names of the storage disk for user storage files and for pending storage requests, respectively. The same disk can be used for both.
5. In the php.ini, configure `post_max_size` and `upload_max_filesize` to allow file uploads of your desired size. Maybe also configure `upload_tmp_dir` to point to a local disk partition with enough space for all temporary upload files.

## Developing

Take a look at the [development guide](https://github.com/biigle/core/blob/master/DEVELOPING.md) of the core repository to get started with the development setup.

Want to develop a new module? Head over to the [biigle/module](https://github.com/biigle/module) template repository.

## Contributions and bug reports

Contributions to BIIGLE are always welcome. Check out the [contribution guide](https://github.com/biigle/core/blob/master/CONTRIBUTING.md) to get started.
