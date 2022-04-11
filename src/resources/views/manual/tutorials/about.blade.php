@extends('manual.base')

@section('manual-title', 'User storage')

@section('manual-content')
    <div class="row">
        <p class="lead">
            Learn how you can upload and manage files to be used in volumes.
        </p>

        <p>
            As a web application, BIIGLE can only work with files that are stored on a server. With <a href="{{route('manual-tutorials', ['volumes', 'remote-volumes'])}}">remote volumes</a>, you set up the server to host the files yourself. As an alternative, BIIGLE allows you to upload files directly from your computer. These files are stored on the BIIGLE server.
        </p>

        <p>
            To upload new files, click on the <button class="btn btn-default btn-xs"><i class="fa fa-upload"></i> Upload Files</button> button on the dashboard. Each time you upload new files, a new "storage request" is created. In the view to create a new storage request, you can create new directories and add files to the directories that should be associated with the storage request. A storage request needs at least one directory but you can also create multiple (nested) directories. To create the first directory, click the <button class="btn btn-default btn-xs"><i class="fa fa-folder"></i> Add directory</button> button and enter the directory name. The new directory will automatically be selected after it has been created. You can now click the <button class="btn btn-default btn-xs"><i class="fa fa-folder"></i> Add subdirectory</button> button to add a new subdirectory or click the <button class="btn btn-default btn-xs"><i class="fa fa-file"></i> Add files</button> button to add files to the selected directory. Click on the name of a directory to select or unselect it. Files or directories can be removed from the storage request with a click on the <button class="btn btn-default btn-xs"><i class="fa fa-trash"></i></button> button that appears when the mouse hovers over a file or directory.
        </p>

        <p>
            When all files and directories have been created, click the <button class="btn btn-success btn-xs"><i class="fa fa-upload"></i> Submit</button> button to upload the files and submit the storage request. New files can be uploaded up to a certain storage quota. If the size of a newly uploaded file exceeds your already used quota, the new file is rejected. Each newly submitted storage request is reviewed by the instance administrators before the files can be used in new volumes. You will receive a notification of the review decision once it has been made. Approved storage requests expire after a certain time. You will receive a notification if one of your storage requests is about to expire. If the storage request is not extended (see below), the expired request and all files will be automatically deleted.
        </p>

        <p>
            To manage your storage requests, click on "Uploaded files" in the dropdown menu of the navbar at the top. This opens a list of all your storage requests. The list indicates the status of each storage request, which can be <span class="label label-default">pending</span>, <span class="label label-success">approved</span> or <span class="label label-danger">expired</span>. If a storage request is about to expire, the status is shown as <span class="label label-warning">expires in x days</span>. To extend a storage request that is about to expire, click the <button class="btn btn-default btn-xs"><i class="fa fa-redo"></i></button> button. To view the files of a storage request, click on the storage request in the list. You can delete files, directories or the whole storage request including all its files with a click on the <button class="btn btn-default btn-xs"><i class="fa fa-trash"></i></button> buttons for the respective items. Deleting a storage request will not delete the volumes (and their annotations) that use files from the storage request. However, the annotation tools will no longer be able to load and display the files.
        </p>

        <p>
            Files of approved storage requests can be used to create new volumes. In the form to create a new volume, select the <button class="btn btn-default btn-xs"><i class="fa fa-upload"></i> Uploaded files</button> file source. This will load a file browser with the uploaded files of all your approved storage requests. Click on a directory or individual files of a directory (using <kbd>Ctrl</kbd>+click) to select files for the new volume.
        </p>
    </div>
@endsection
