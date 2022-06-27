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
            name : 'Title 1',
            element : 'h1',
            attributes : { 'class': 'ck ck-title' }
        },
        {
            name : 'Title 2',
            element : 'h2',
            attributes : { 'class': 'ck ck-title2' }
        },
        {
            name : 'Alert Success',
            element : 'div',
            attributes : { 'class': 'alert alert-success' }
        },
        {
            name : 'Alert Info',
            element : 'div',
            attributes : { 'class': 'alert alert-info' }
        },
        {
            name : 'Alert Warning',
            element : 'div',
            attributes : { 'class': 'alert alert-warning' }
        },
        {
            name : 'Alert Danger',
            element : 'div',
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
    config.filebrowserBrowseUrl = '{{ _p.web_lib ~ 'elfinder/filemanager.php?' }}{{ course_condition }}';
    config.videobrowserBrowseUrl = '{{ _p.web_lib ~ 'elfinder/filemanager.php?' }}{{ course_condition }}';

    {% if enter_mode %}
        config.enterMode = {{ enter_mode }};
    {% endif %}

    // Allows to use "class" attribute inside divs and spans.
    config.allowedContent = true;
    // Option to set the "styles" menu
    config.contentsCss = [
        '{{ bootstrap_css }}',
        '{{ font_awesome_css }}',
        '{{ css_editor }}',
    ];

    config.language_list = ['{{ language_list }}'];

    config.qMarkersRollsUrl = '{{ _p.web_ajax }}exercise.ajax.php?a=get_quiz_embeddable';

    var videoTypesMap = {
        dailymotion: 'DailyMotion',
        facebook: 'Facebook',
        twitch: 'Twitch',
        vimeo: 'Vimeo',
        youtube: 'YouTube'
    };
    config.videoTypes = [
        [ 'MP4', 'video/mp4' ],
        [ 'WebM', 'video/webm' ],
    ];
    {% set video_renderers = 'video_player_renderers'|api_get_configuration_value %}
    {% if video_renderers and video_renderers.renderers %}
        {{ video_renderers.renderers|json_encode }}.forEach(function(rendererName) {
            if (videoTypesMap.hasOwnProperty(rendererName)) {
                config.videoTypes.push( [videoTypesMap[rendererName], 'video/' + rendererName] );
            }
        });
    {% endif %}

    config.font_names = "{{ font_names }}";
};

// Sets default target to "_blank" in link plugin
CKEDITOR.on('dialogDefinition', function (ev) {
    if (ev.data.name == 'link'){
        ev.data.definition.getContents('target').get('linkTargetType')['default']='_blank';
    }
});