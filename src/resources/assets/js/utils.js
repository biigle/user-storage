export let sizeForHumans = function (size) {
    let unit = '';
    let units = ['kB', 'MB', 'GB', 'TB'];
    do {
        size /= 1000;
        unit = units.shift();
    } while (size > 1000 && units.length > 0);

    return `${size.toFixed(2)} ${unit}`;
};
