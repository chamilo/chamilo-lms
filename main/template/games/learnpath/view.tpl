<div class="container">
<header>
    <div class="row">
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menu-bar-top">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <div class="navbar-brand" href="#">
                        {{ logo }}
                    </div>
                </div>
                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse" id="menu-bar-top">
                    <ul class="nav navbar-nav navbar-right">
                        {% for item in list %}
                        {% if item['key'] == 'homepage' or item['key'] == 'my-course' %}
                        <li><a href="{{ item['url'] }}">{{ item['title'] }}</a></li>
                        {% endif %}
                        {% endfor %}

                        {% if _u.logged == 0 %}
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                Iniciar Sesión<span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li class="login-menu">
                                    {# if user is not login show the login form #}
                                    {% block login_form %}

                                    {% include template ~ "/layout/login_form.tpl" %}

                                    {% endblock %}
                                </li>
                            </ul>
                        </li>
                        {% endif %}
                        {% if _u.logged == 1 %}
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                {{ _u.complete_name }}<span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">

                                {% for item in list %}
                                {% if item['key'] != 'my-space' and item['key'] != 'dashboard' and item['key'] != 'homepage' and item['key'] != 'my-course' %}
                                <li><a href="{{ item['url'] }}">{{ item['title'] }}</a></li>
                                {% endif %}
                                {% endfor %}
                                <li class="divider"></li>
                                <li>
                                    <a title="{{ "Logout"|get_lang }}" href="{{ logout_link }}">
                                        <i class="fa fa-sign-out"></i>{{ "Logout"|get_lang }}
                                    </a>
                                </li>
                            </ul>
                        </li>
                        {% endif %}
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div><!-- /.container-fluid -->
        </nav>
    </div>
</header>
</div>

<div id="learning_path_main" style="width:100%; height: 100%;">
    <button id="touch-button" class="btn btn-primary"><i class="fa fa-bars"></i></button>
    <div class="container">
        <div class="row">
            <div id="learning_path_left_zone" class="sidebar-scorm">
                    <div id="scorm-info" class="panel panel-default">
                        <div id="panel-scorm" class="panel-body">
                            <div id="lp_navigation_elem" class="navegation-bar">
                                <div class="ranking-scorm">
                                    {% if gamification_mode == 1 %}
                                    <div class="row">
                                        <div class="col-md-7">
                                            {% set lp_stars = oLP.getCalculateStars() %}
                                            {% if lp_stars > 0%}
                                                {% for i in 1..lp_stars %}
                                                    <i class="fa fa-star"></i>
                                                {% endfor %}
                                            {% endif %}
                                            {% if lp_stars < 4 %}
                                                {% for i in 1..4 - lp_stars %}
                                                    <i class="fa fa-star plomo"></i>
                                                {% endfor %}
                                            {% endif %}
                                        </div>
                                        <div class="col-md-5 text-points">
                                            {{ "XPoints"|get_lang|format(oLP.getCalculateScore()) }}
                                        </div>
                                    </div>
                                    {% endif %}
                                </div>
                                <div id="progress_bar">
                                    {{ progress_bar }}
                                </div>

                            </div>
                        </div>
                    </div>


                {# TOC layout #}
                <div id="toc_id" name="toc_name">
                    <div id="learning_path_toc" class="scorm-list">
                        {{ oLP.get_html_toc(toc_list) }}
                    </div>
                </div>
                {# end TOC layout #}

            </div>

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
