<script>
$(document).ready(function(){
    $("div#log_content_cleaner").bind("click", function() {
        $("div#log_content").empty();
    });
});

var chamilo_xajax_handler = window.oxajax;
chamilo_courseCode = '{{ course_code }}';
</script>
<script>
    var sv_user = {{ _u.user_id }};
    var sv_course = chamilo_courseCode;
    var sv_sco = {{ lp_id }};

        // Resize right and left pane to full height (HUB 20-05-2010).
    function updateContentHeight() {
        document.body.style.overflow = 'hidden';
        var IE = window.navigator.appName.match(/microsoft/i);
        var hauteurHeader = document.getElementById('header').offsetHeight;
        var hauteurTitre = document.getElementById('scorm_title').offsetHeight;
        var topBar = $('#learning_path_breadcrumb_zone').length > 0 ? $('#learning_path_breadcrumb_zone').outerHeight() : 0;
        var panel_body = $('.panel-body').length > 0 ? $('.panel-body').outerHeight() : 0;
        var totalHeight = hauteurHeader + hauteurTitre + panel_body+topBar;
        var innerHauteur = (IE) ? document.body.clientHeight : window.innerHeight;

        document.getElementById('inner_lp_toc').style.height = innerHauteur - totalHeight + "px";

        if (document.getElementById('content_id')) {
            document.getElementById('content_id').style.height = innerHauteur - topBar + 'px';
        }
    }
    window.onload = updateContentHeight;
    window.onresize = updateContentHeight;
</script>
<script src="{{ _p.web_code_path }}newscorm/js/documentapi.js" type="text/javascript"></script>
<script src="{{ _p.web_code_path }}newscorm/js/storageapi.js"></script>

{% if lp.mode == 'fullscreen' %}
    <script>window.open('$src','content_id','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');</script>
{% endif %}

{% if show_glossary == 'true' %}

    {% if glossary_type == 'ismanual' %}
        <script>
        $.frameReady(function () {},
            "top.content_name", {
                load:[
                {
                    type:"script",
                    id:"_fr1",
                    src:"{{ _p.web_library_js_path }}jquery.js"
                },
                {
                    type:"script",
                    id:"_fr4",
                    src:"{{ _p.web_library_js_path }}jquery-ui/js/jquery-ui.custom.js"
                },
                {
                    type:"stylesheet",
                    id:"_fr5",
                    src:"{{ _p.web_library_js_path }}jquery-ui/css/smoothness/jquery-ui.custom.min.css"
                },
                {
                    type:"script",
                    id:"_fr2",
                    src:"{{ _p.web_library_js_path }}jquery.highlight.js"
                }
                ]
            }
        );
        </script>
    {% else %}
        <script>
            $.frameReady(function () {},
                "top.content_name",
                { load:[
                    {
                        type:"script",
                        id:"_fr1",
                        src:"{{ _p.web_library_js_path }}jquery.js"},
                    {
                        type:"script",
                        id:"_fr4",
                        src:"{{ _p.web_library_js_path }}jquery-ui/js/jquery-ui.custom.js"},
                    {
                        type:"stylesheet",
                        id:"_fr5",
                        src:"{{ _p.web_library_js_path }}jquery-ui/css/smoothness/jquery-ui-custom.css"},
                    {
                        type:"script",
                        id:"_fr2",
                        src:"{{ _p.web_library_js_path }}jquery.highlight.js"
                    }
                ]}
            );
        </script>
    {% endif %}
{% endif %}
