<div id="learning_path_main" class="{{ is_allowed_to_edit ? 'lp-view-include-breadcrumb' }}">
    {% if is_allowed_to_edit %}
        <div id="learning_path_breadcrumb_zone" class="hidden-xs">
            {{ breadcrumb }}
        </div>
    {% endif %}
    <button id="touch-button" class="btn btn-default btn-lg btn-touch">
        <span class="fa fa-chevron-left fa-2x" aria-hidden="true"></span><span class="sr-only">{{ 'Hide'|get_lang }}</span>
    </button>
            <div id="learning_path_left_zone" class="sidebar-scorm">
                <div class="lp-view-zone-container">
                <div class="panel-group" id="scorm-info-accordion" role="tablist" aria-multiselectable="true">
                    <div id="scorm-info" class="panel panel-default">
                        <div class="panel-heading text-center" role="tab" id="scorm-info-panel-title">
                            <a id="ui-option" role="button" data-toggle="collapse" data-parent="#scorm-info-accordion" href="#scorm-info-panel-content" aria-expanded="true" aria-controls="scorm-info-panel-content">
                                <span class="fa fa-chevron-up fa-fw" aria-hidden="true"></span>
                                <span class="sr-only">{{ 'Hide'|get_lang }}</span>
                            </a>
                        </div>
                        <div id="scorm-info-panel-content" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="scorm-info-panel-title">
                            {# Author image preview #}
                            <div id="panel-scorm" class="panel-body">
                                <div class="image-avatar">
                                    {% if lp_author == '' %}
                                       <div class="text-center">
                                            {{ lp_preview_image }}
                                        </div>
                                    {% else %}
                                        <div class="media">
                                            <div class="media-left">
                                                {{ lp_preview_image }}
                                            </div>
                                            <div class="media-body">
                                                <div class="description-autor"> {{ lp_author }} </div>
                                            </div>
                                        </div>
                                    {% endif %}
                                </div>

                                {% if show_audio_player %}
                                    <div id="lp_media_file">
                                        {{ media_player }}
                                    </div>
                                {% endif %}

                                {% if gamification_mode == 1 %}
                                    <hr>
                                    <!--- gamification -->    
                                    <div id="scorm-gamification">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                {% if gamification_stars > 0 %}
                                                    {% for i in 1..gamification_stars %}
                                                        <em class="fa fa-star level"></em>
                                                    {% endfor %}
                                                {% endif %}

                                                {% if gamification_stars < 4 %}
                                                    {% for i in 1..4 - gamification_stars %}
                                                        <em class="fa fa-star"></em>
                                                    {% endfor %}
                                                {% endif %}
                                            </div>
                                            <div class="col-xs-6 text-right">
                                                {{ "XPoints"|get_lang|format(gamification_points) }}
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12 navegation-bar">
                                                <div id="progress_bar">
                                                    {{ progress_bar }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                   <!--- end gamification -->          
                                {% else %}         
                                    <div id="progress_bar">
                                        {{ progress_bar }}
                                    </div>
                                {% endif %}

                                {{ teacher_toc_buttons }}
                            </div>
                        </div>
                    </div>
                </div>

                {# TOC layout #}
                <div id="toc_id" class="scorm-body" name="toc_name">
                    <div id="learning_path_toc" class="scorm-list">
                        {{ lp_html_toc }}
                    </div>
                </div>
                {# end TOC layout #}
                </div>
            </div>
            {# end left zone #}

            {# <div id="hide_bar" class="scorm-toggle" style="display:inline-block; width: 25px; height: 1000px;"></div> #}

            {# right zone #}
            <div id="learning_path_right_zone" class="content-scorm">
                <div class="lp-view-zone-container">
                    <div id="lp_navigation_elem" class="navegation-bar pull-right text-right">
                        <a href="{{ button_home_url }}" class="btn btn-link" target="_self" onclick="javascript: window.parent.API.save_asset();">
                            <em class="fa fa-home fa-2x"></em> <span class="hidden-xs hidden-sm">{{ button_home_text }}</span>
                        </a>
                        {{ navigation_bar }}
                    </div>

                    <h1 class="scorm-title">{{ lp_title_scorm }}</h1>

                    <div class="lp-view-tabs">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#lp-view-content" aria-controls="lp-view-content" role="tab" data-toggle="tab">
                                    <span class="fa fa-book fa-2x fa-fw" aria-hidden="true"></span><span class="sr-only">{{ 'Lesson'|get_lang }}</span>
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#lp-view-forum" aria-controls="lp-view-forum" role="tab" data-toggle="tab">
                                    <span class="fa fa-commenting-o fa-2x fa-fw" aria-hidden="true"></span><span class="sr-only">{{ 'Forum'|get_lang }}</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="lp-view-content">
                                {% if lp_mode == 'fullscreen' %}
                                    <iframe id="content_id_blank" name="content_name_blank" src="blank.php" border="0" frameborder="0" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
                                {% else %}
                                    <iframe id="content_id" name="content_name" src="{{ iframe_src }}" border="0" frameborder="0" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
                                {% endif %}
                            </div>
                            <div role="tabpanel" class="tab-pane" id="lp-view-forum">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {# end right Zone #}
</div>

<script>
    (function () {
        var LPViewUtils = {
            setHeightLPToc: function () {
                var scormInfoHeight = $('#scorm-info-accordion').outerHeight(true);

                $('#learning_path_toc').css({
                    top: scormInfoHeight
                });
            }
        };

        $(document).on('ready', function () {
            $('#touch-button').click(function() {
                $('#learning_path_main').toggleClass('lp-view-collapsed');

                var self = $(this);
                self.toggleClass('show-touch');

                var icon = self.children('span.fa');

                if (icon.is('.fa-chevron-left')) {
                    icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');

                    return;
                }

                icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
            });
            
            $('.lp-view-tabs').on('click', '.disabled', function (e) {
                e.preventDefault();
            });

            $('a#ui-option').on('click', function (e) {
                e.preventDefault();

                var icon = $(this).children('.fa');

                if (icon.is('.fa-chevron-up')) {
                    icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');

                    return;
                }

                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });

            $('#scorm-info-panel-content').on('shown.bs.collapse', function () {
                LPViewUtils.setHeightLPToc();
            });

            $('#scorm-info-panel-content').on('hidden.bs.collapse', function () {
                LPViewUtils.setHeightLPToc();
            });

            LPViewUtils.setHeightLPToc();

            $('.scorm_item_normal a, #scorm-previous, #scorm-next').on('click', function () {
                $('.lp-view-tabs').fadeOut();
            });

            $('#learning_path_right_zone #lp-view-content iframe').on('load', function () {
                $('.lp-view-tabs a[href="#lp-view-content"]').tab('show');

                $('.lp-view-tabs').fadeIn();
            });

            loadForumThead({{ lp_id }}, {{ lp_current_item_id }});

            {% if glossary_extra_tools in glossary_tool_availables %}
                // Loads the glossary library.
                (function () {
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
                                    { type:"script", id:"_fr2", src:"{{ _p.web_lib }}javascript/jquery.highlight.js"},
                                    {{ fix_link }}
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
                                    { type:"script", id:"_fr2", src:"{{ _p.web_lib }}javascript/jquery.highlight.js"},
                                    {{ fix_link }}
                                ]
                            }
                        );
                    {% elseif fix_link != '' %}
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
                                    {{ fix_link }}
                                ]
                            }
                        );
                    {% endif %}
                })();
            {% endif %}
        });

        $(window).on('resize', function () {
            LPViewUtils.setHeightLPToc();
        });
    })();
</script>
