export let sizeForHumans = function (size) {
    let unit = '';
    let units = ['kB', 'MB', 'GB', 'TB'];
    do {
        size /= 1000;
        unit = units.shift();
    } while (size > 1000 && units.length > 0);

    return `${size.toFixed(2)} ${unit}`;
};

export let buildDirectoryTree = function (request) {
    let root = {
        name: '',
        directories: {},
        files: [],
    };

    if (request.files) {
        request.files.forEach(function (file) {
            let breadcrumbs = file.path.split('/');
            let filename = breadcrumbs.pop();
            let currentDir = root;
            breadcrumbs.forEach(function (name) {
                if (!currentDir.directories.hasOwnProperty(name)) {
                    currentDir.directories[name] = {
                        name: name,
                        directories: {},
                        files: [],
                    };
                }

                currentDir = currentDir.directories[name];
            });

            currentDir.files.push({
                id: file.id,
                name: filename,
            });
        });
    }

    return root;
};
