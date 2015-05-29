<div id="learning_path_main" style="width:100%; height: 100%;">
    {% if is_allowed_to_edit %}
        <div class="row">
            <div id="learning_path_breadcrumb_zone" class="col-md-12">
                {{ breadcrumb }}
            </div>
        </div>
    {% endif %}
    <button id="touch-button" class="btn btn-primary"><i class="fa fa-bars"></i></button>
    <div class="container-fluid">
        <div class="row">
            <div id="learning_path_left_zone" class="sidebar-scorm">
                {% if gamification_mode == 1 %}
                    <div class="row">
                        <div class="col-xs-8">
                            {% set lp_stars = oLP.getCalculateStars() %}
                            {% if lp_stars > 0%}
                                {% for i in 1..lp_stars %}
                                    <i class="fa fa-star fa-2x"></i>
                                {% endfor %}
                            {% endif %}

                            {% if lp_stars < 4 %}
                                {% for i in 1..4 - lp_stars %}
                                    <i class="fa fa-star-o fa-2x"></i>
                                {% endfor %}
                            {% endif %}
                        </div>
                        <div class="col-xs-4 text-right">
                            {{ "XPoints"|get_lang|format(oLP.getCalculateScore()) }}
                        </div>
                    </div>
                {% else %}
                    <div id="scorm-info" class="panel panel-default">
                        <div class="panel-heading">
                            <a id="ui-option">
                                <i id="icon-down" class="fa fa-chevron-down hidden"></i>
                                <i id="icon-up" class="fa fa-chevron-up"></i>
                            </a>
                        </div>
                        {# Author image preview #}
                        <div id="panel-scorm" class="panel-body">
                            <a href="{{ button_home_url }}" class="btn btn-success btn-block" target="_self" onclick="javascript: window.parent.API.save_asset();">
                                <i class="fa fa-home"></i> {{ button_home_text }}
                            </a>

                            <div class="image-avatar">
                                {% if oLP.get_preview_image() %}
                                    <img src="{{ oLP.get_preview_image_path() }}" width="104" height="96">
                                {% else %}
                                    <img src="{{ "unknown_250_100.jpg"|icon }}" width="104" height="96">
                                {% endif %}
                            </div>

                            <div id="lp_navigation_elem" class="navegation-bar">
                                {{ navigation_bar }}

                                <div id="progress_bar">
                                    {{ progress_bar }}
                                </div>
                            </div>

                            <div class="description-autor">
                                {{ oLP.get_author() }}
                            </div>

                            {% if show_audio_player %}
                                <div id="lp_media_file">
                                    {{ media_player }}
                                </div>
                            {% endif %}
                        </div>
                    </div>
                {% endif %}

                {# TOC layout #}
                <div id="toc_id" name="toc_name">
                    <div id="learning_path_toc" class="scorm-list">
                        {{ oLP.get_html_toc(toc_list) }}
                    </div>
                </div>
                {# end TOC layout #}

            </div>
            {# end left zone #}

            {# <div id="hide_bar" class="scorm-toggle" style="display:inline-block; width: 25px; height: 1000px;"></div> #}

            {# right zone #}
            <div id="learning_path_right_zone" style="height:100%" class="content-scorm">
                {% if oLP.mode == 'fullscreen' %}
                    <iframe id="content_id_blank" name="content_name_blank" src="blank.php" border="0" frameborder="0" style="width: 100%; height: 100%" ></iframe>
                {% else %}
                    <iframe id="content_id" name="content_name" src="{{ iframe_src }}" border="0" frameborder="0" style="display: block; width: 100%; height: 100%"></iframe>
                {% endif %}
            </div>
            {# end right Zone #}

            {{ navigation_bar_bottom }}
        </div>
    </div>
</div>

<script>
    // Resize right and left pane to full height (HUB 20-05-2010).
    var updateContentHeight = function () {
        document.body.style.overflow = 'hidden';
        var IE = window.navigator.appName.match(/microsoft/i);

        /* Identified new height */
        var heightControl = $('#control-bottom').height();
        var heightBreadcrumb = ($('#learning_path_breadcrumb_zone').height()) ? $('#learning_path_breadcrumb_zone').height() : 0;

        var heightScormInfo = $('#scorm-info').height();

        var heightTop = heightScormInfo + 100;

        //heightTop = (heightTop > 300)? heightTop : 300;

        var innerHeight = $(window).height();

        if (innerHeight <= 640) {
            $('#inner_lp_toc').css('height', innerHeight - heightTop + "px");
            $('#content_id').css('height', innerHeight - heightControl + "px");
        } else {
            $('#inner_lp_toc').css('height', innerHeight - heightBreadcrumb - heightTop + "px");
            $('#content_id').css('height', innerHeight - heightControl + "px");
        }

        //var innerHeight = (IE) ? document.body.clientHeight : window.innerHeight ;

        // Loads the glossary library.
        {% if glossary_extra_tools in glossary_tool_availables %}
                {% if show_glossary_in_documents == 'ismanual' %}
                    $.frameReady(
                        function(){
                            //  $("<div>I am a div courses</div>").prependTo("body");
                        },
                        "top.content_name",
                        {
                            load: [
                                { type:"script", id:"_fr1", src:"{{ jquery_web_path }}"},
                                { type:"script", id:"_fr4", src:"{{ jquery_ui_js_web_path }}"},
                                { type:"stylesheet", id:"_fr5", src:"{{ jquery_ui_css_web_path }}"},
                                { type:"script", id:"_fr2", src:"{{ _p.web_lib }}javascript/jquery.highlight.js"}
                            ]
                        }
                    );
                {% elseif show_glossary_in_documents == 'isautomatic' %}
                    $.frameReady(
                        function(){
                            //  $("<div>I am a div courses</div>").prependTo("body");
                        },
                        "top.content_name",
                        {
                            load: [
                                { type:"script", id:"_fr1", src:"{{ jquery_web_path }}"},
                                { type:"script", id:"_fr4", src:"{{ jquery_ui_js_web_path }}"},
                                { type:"stylesheet", id:"_fr5", src:"{{ jquery_ui_css_web_path }}"},
                                { type:"script", id:"_fr2", src:"{{ _p.web_lib }}javascript/jquery.highlight.js"}
                            ]
                        }
                    );
                {% endif %}
        {% endif %}
    };

    $(document).ready(function() {
        updateContentHeight();

        $('#touch-button').children().click(function(){
            updateContentHeight();
        });

        $(window).resize(function() {
            updateContentHeight();
        });
    });

    window.onload = updateContentHeight();
    window.onresize = updateContentHeight();

    $(document).ready(function(){
        $("#icon-down").click(function(){
            $("#icon-up").removeClass("hidden");
            $(this).addClass("hidden");

            $('#panel-scorm').slideDown("slow",function(){
                updateContentHeight();
            });
        });

        $("#icon-up").click(function(){
            $("#icon-down").removeClass("hidden");
            $(this).addClass("hidden");
            $('#panel-scorm').slideUp("slow",function(){
                updateContentHeight();
            });
        });
    });
</script>
