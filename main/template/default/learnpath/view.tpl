<div id="learning_path_main" class="{{ is_allowed_to_edit ? 'lp-view-include-breadcrumb' }} {{ lp_mode == 'embedframe' ? 'lp-view-collapsed' }}">
    {% if is_allowed_to_edit %}
        <div id="learning_path_breadcrumb_zone" class="hidden-xs">
            {{ breadcrumb }}
        </div>
    {% endif %}
        <div id="learning_path_left_zone" class="sidebar-scorm">
            <div class="lp-view-zone-container">
                <div id="scorm-info">
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
                            <div id="lp_media_file" class="audio-scorm">
                                {{ media_player }}
                            </div>
                        {% endif %}
                        {% if gamification_mode == 1 %}
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
                        <div class="visible-xs-block movil-toolbar">
                            <ul class="btn-movil">
                                <li>
                                    <a href="{{ button_home_url }}" class="icon-toolbar" target="_self" onclick="javascript: window.parent.API.save_asset();">
                                        <em class="fa fa-home"></em> <span class="hidden-xs hidden-sm"></span>
                                    </a>
                                </li>
                                <li>
                                    <button type="button" id="lp-view-expand-button" class="icon-toolbar expand visible-xs-block">
                                        <span class="fa fa-expand" aria-hidden="true"></span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        {{ teacher_toc_buttons }}
                    </div>
                </div>
            {# TOC layout #}
            <div id="toc_id" class="scorm-body" name="toc_name">
                <div id="learning_path_toc" class="scorm-list">
                    <h1 class="scorm-title">{{ lp_title_scorm }}</h1>
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
                    <a href="#" id="lp-view-expand-toggle" class="icon-toolbar expand" role="button">
                        {% if lp_mode == 'embedframe' %}
                            <span class="fa fa-compress" aria-hidden="true"></span>
                            <span class="sr-only">{{ 'Expand'|get_lang }}</span>
                        {% else %}
                            <span class="fa fa-expand" aria-hidden="true"></span>
                            <span class="sr-only">{{ 'Expand'|get_lang }}</span>
                        {% endif %}
                    </a>
                    <a id="home-course" href="{{ button_home_url }}" class="icon-toolbar" target="_self" onclick="javascript: window.parent.API.save_asset();">
                        <em class="fa fa-home"></em> <span class="hidden-xs hidden-sm"></span>
                    </a>
                    {{ navigation_bar }}
                </div>

                <div class="lp-view-tabs">
                    <ul id="navTabs" class="nav nav-tabs" role="tablist">
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
                            <div id="wrapper-iframe" style="width:100%; height:100%">
                            {% if lp_mode == 'fullscreen' %}
                                <iframe id="content_id_blank" name="content_name_blank" src="blank.php" style="width:100%; height:100%" border="0" frameborder="0" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
                            {% else %}
                                <iframe id="content_id" name="content_name" src="{{ iframe_src }}" style="width:100%; height:100%" border="0" frameborder="0" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
                            {% endif %}
                            </div>
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
            var scormInfoHeight = $('#scorm-info').outerHeight(true);

            $('#learning_path_toc').css({
                top: scormInfoHeight
            });
        }
    };

    $(document).on('ready', function () {
        if (/iPhone|iPod|iPad/.test(navigator.userAgent)) {
          $('#wrapper-iframe').css({
            'overflow' : 'auto',
            'position' : 'relative',
            '-webkit-overflow-scrolling': 'touch'
          });
        }

        {% if lp_mode == 'embedframe' %}
            //$('#learning_path_main').addClass('lp-view-collapsed');
            $('#lp-view-expand-button, #lp-view-expand-toggle').on('click', function (e) {
            e.preventDefault();

            $('#learning_path_main').toggleClass('lp-view-collapsed');

            $('#lp-view-expand-toggle span.fa').toggleClass('fa-compress');
            $('#lp-view-expand-toggle span.fa').toggleClass('fa-expand');
        });
        {% else %}
        $('#lp-view-expand-button, #lp-view-expand-toggle').on('click', function (e) {
            e.preventDefault();

            $('#learning_path_main').toggleClass('lp-view-collapsed');

            $('#lp-view-expand-toggle span.fa').toggleClass('fa-expand');
            $('#lp-view-expand-toggle span.fa').toggleClass('fa-compress');
        });

        {% endif %}

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

        LPViewUtils.setHeightLPToc();

        $('.scorm_item_normal a, #scorm-previous, #scorm-next').on('click', function () {
            $('.lp-view-tabs').animate({opacity: 0}, 500);
        });

        $('#learning_path_right_zone #lp-view-content iframe').on('load', function () {
            $('.lp-view-tabs a[href="#lp-view-content"]').tab('show');

            $('.lp-view-tabs').animate({opacity: 1}, 500);
        });

        loadForumThread({{ lp_id }}, {{ lp_current_item_id }});
        checkCurrentItemPosition({{ lp_current_item_id }});

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

        $('iframe#content_id')
            .on('load', function () {
                $.frameReady(function () {
                }, 'top.content_name', {
                    load: [
                        {type: 'script', id: '_fr1', src: '{{ _p.web }}web/assets/jquery/dist/jquery.min.js'},
                        {type: 'script', id: '_fr7', src: '{{ _p.web }}web/assets/MathJax/MathJax.js?config=AM_HTMLorMML'},
                        {type: 'script', id: '_fr4', src: '{{ _p.web }}web/assets/jquery-ui/jquery-ui.min.js'},
                        {type: 'stylesheet', id: '_fr5', src: '{{ _p.web }}web/assets/jquery-ui/themes/smoothness/jquery-ui.min.css'},
                        {type: 'stylesheet', id: '_fr6', src: '{{ _p.web }}web/assets/jquery-ui/themes/smoothness/theme.css'},
                        {type: 'script', id: '_fr2', src: '{{ _p.web_lib }}javascript/jquery.highlight.js'},
                        {type: 'script', id: '_fr3', src: '{{ _p.web_main }}glossary/glossary.js.php'}
                    ]
                });
            });
    });

    $(window).on('resize', function () {
        LPViewUtils.setHeightLPToc();
    });
})();
</script>
