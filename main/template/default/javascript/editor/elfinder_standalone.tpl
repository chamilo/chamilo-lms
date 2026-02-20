{% set finderFolder = _p.web ~ 'vendor/studio-42/elfinder/' %}
<!-- elFinder CSS (REQUIRED) -->
<link rel="stylesheet" type="text/css" media="screen" href="{{ finderFolder }}css/elfinder.full.css">
<link rel="stylesheet" type="text/css" media="screen" href="{{ finderFolder }}css/theme.css">

<!-- elFinder JS (REQUIRED) -->
<script type="text/javascript" src="{{ finderFolder }}js/elfinder.full.js"></script>

<!-- elFinder translation (OPTIONAL) -->
{{ elfinder_translation_file }}

<script charset="utf-8">
    // Helper function to get parameters from the query string.
    function getUrlParam(paramName) {
        var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i');
        var match = window.location.search.match(reParam);
        return (match && match.length > 1) ? match[1] : '';
    }

    $().ready(function() {
        var funcNum = getUrlParam('CKEditorFuncNum');
        var sessionId = {{ session_id }};
        // Pattern matches Chamilo's document suffix format: __<sessionId>__<groupId>
        // First captured group is always session ID, second is always group ID.
        var suffixPattern = /__(\d+)__(\d+)(?:\.[^.]+)?$/;
        var elf = $('#elfinder').elfinder({
            url : '{{ _p.web_lib ~ 'elfinder/connectorAction.php?' }}{{ course_condition }}',  // connector URL (REQUIRED)
            getFileCallback : function(file) {
                var match = suffixPattern.exec(file.name);
                if (match && parseInt(match[1]) !== 0 && parseInt(match[1]) !== sessionId) {
                    return;
                }
                if (window.opener) {
                    if (window.opener.CKEDITOR) {
                        window.opener.CKEDITOR.tools.callFunction(funcNum, file.url);
                    }

                    if (window.opener.addImageToQuestion) {
                        window.opener.addImageToQuestion(file.url, {{ question_id }});
                    }
                }

                window.close();
            },
            startPathHash: 'l2_Lw', // Sets the course driver as default
            resizable: false,
            lang: '{{ elfinder_lang }}',
            handlers : {
                sync : function(event, elfinderInstance) {
                    var files = elfinderInstance.files();
                    for (var key in files) {
                        var match = suffixPattern.exec(files[key].name);
                        if (match && parseInt(match[1]) !== 0 && parseInt(match[1]) !== sessionId) {
                            $('#' + files[key].hash).hide();
                        }
                    }
                }
            }
        }).elfinder('instance');
    });
</script>
<div id="elfinder"></div>
