/* Ckeditor global configuration file */

CKEDITOR.editorConfig = function (config) {
    // Define changes to default configuration here.
    // For complete reference see:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    // Remove some buttons provided by the standard plugins, which are
    // not needed in the Standard(s) toolbar.
    //config.removeButtons = 'Underline,Subscript,Superscript';

    // Set the most common block elements.
    config.format_tags = 'p;h1;h2;h3;h4;h5;h6;pre';

    // Simplify the dialog windows.
    config.removeDialogTabs = 'image:advanced;link:advanced';

    config.templates_files  = [
        '{{ _p.web_main ~ 'inc/lib/elfinder/templates.php'}}'
    ];

    {% if moreButtonsInMaximizedMode %}
        config.toolbar = 'minToolbar';
        config.smallToolbar = 'minToolbar';
        config.maximizedToolbar = 'maxToolbar';
    {% endif %}

    // File manager (elFinder)
    config.filebrowserBrowseUrl = '{{ _p.web_lib ~ 'elfinder/filemanager.php' }}';

    // Allows to use "class" attribute inside divs and spans.
    config.allowedContent = true;

    config.customConfig = '{{ _p.web_main ~ 'inc/lib/javascript/ckeditor/config_js.php'}}';
};
