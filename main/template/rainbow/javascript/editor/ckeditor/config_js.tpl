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
    //Style for default CKEditor Chamilo LMS
    config.stylesSet = [
        { 
            name : 'Titre',
            element : 'h2',
            attributes : { 'class': 'ck ck-titre' }
        },
        { 
            name : 'Parcours',
            element : 'h4',
            attributes : { 'class': 'ck ck-parcours' }
        },
        { 
            name : 'Etape',
            element : 'h5',
            attributes : { 'class': 'ck ck-etape' }
        },
        { 
            name : 'Texte',
            element : 'p',
            attributes : { 'class': 'ck ck-texte' }
        },
        { 
            name : 'Source',
            element : 'p',
            attributes : { 'class': 'ck ck-source' }
        },
        { 
            name : 'Consignes',
            element : 'p',
            attributes : { 'class': 'ck ck-consignes' }
        },
        { 
            name : 'Alert Danger',
            element : 'p',
            attributes : { 'class': 'alert alert-danger' }
        },
        { 
            name : 'Section Article' ,
            element : 'h3' ,
            attributes : { 'class': 'ck ck-article' }
        }, {
            name : 'Paragraph box' ,
            element : 'p' ,
            attributes: { 'class': 'ck-paragraph-box' }
        }, {
            name : 'Superscript' ,
            element : 'sup'
        },
        {
            name : 'Subscript' ,
            element : 'sub'
        },
        {
            name : 'Strikethrough' ,
            element : 'del'
        },
        {
            name : 'Underlined' ,
            element : 'ins'
        },
        {
            name : 'Stand Out' ,
            element : 'span',
            attributes: { 'class':'ck-stand-out'}
        },
        {
            name : 'Separate Style 1' ,
            element : 'hr',
            attributes: { 'class':'ck-style1'}
        },
        {
            name : 'Separate Style 2' ,
            element : 'hr',
            attributes: { 'class':'ck-style2'}
        },
        {
            name : 'Separate Style 3' ,
            element : 'hr',
            attributes: { 'class':'ck-style3'}
        }
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
    config.contentsCss = '{{ cssEditor }}';

    config.customConfig = '{{ _p.web_main ~ 'inc/lib/javascript/ckeditor/config_js.php'}}';
};
