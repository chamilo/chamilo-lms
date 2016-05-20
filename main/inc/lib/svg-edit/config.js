/* see https://code.google.com/p/svg-edit/wiki/ConfigOptions */
svgEditor.setConfig({
    extensions: [
        'ext-php_savefile_chamilo.js',
        'ext-eyedropper.js',
        'ext-shapes.js',
        'ext-polygon.js',
        'ext-star.js'
    ],
    noStorageOnLoad: 'true',
    selectNew: true,
    no_save_warning: true,
    emptyStorageOnDecline: true,
    iconsize: 'm',
	allowedOrigins: [window.location.origin]
    // May be 'null' (as a string) when used as a file:// URL
});
