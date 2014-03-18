{% extends app.template_style ~ "/layout/no_layout.tpl" %}

{% block body %}
    <!-- elFinder CSS (REQUIRED) -->
    <link rel="stylesheet" type="text/css" media="screen" href="{{ _p.web_lib }}elfinder/css/elfinder.min.css">
    <link rel="stylesheet" type="text/css" media="screen" href="{{ _p.web_lib }}elfinder/css/theme.css">

    <!-- elFinder JS (REQUIRED) -->
    <script type="text/javascript" src="{{ _p.web_lib }}elfinder/js/elfinder.min.js"></script>

    <!-- elFinder translation (OPTIONAL) -->
    <script type="text/javascript" src="{{ _p.web_lib }}elfinder/js/i18n/elfinder.ru.js"></script>

    <script type="text/javascript">

        var FileBrowserDialogue = {
            init: function() {
                // Here goes your code for setting your custom things onLoad.
            },
            mySubmit: function (URL) {
                // pass selected file path to TinyMCE
                top.tinymce.activeEditor.windowManager.getParams().setUrl(URL);

                // close popup window
                top.tinymce.activeEditor.windowManager.close();
            }
        }

        $().ready(function() {
            var elf = $('#elfinder').elfinder({
                // set your elFinder options here
                url : '{{ url('editor.controller:connectorAction') }}',  // connector URL (REQUIRED)
                getFileCallback: function(file) { // editor callback
                    // actually file.url - doesnt work for me, but file does. (elfinder 2.0-rc1)
                    FileBrowserDialogue.mySubmit(file.url); // pass selected file path to TinyMCE
                }
            }).elfinder('instance');
        });
    </script>
    <div id="elfinder"></div>
{% endblock %}
