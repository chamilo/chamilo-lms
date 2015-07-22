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
                                    {% if gamification_stars and gamification_score %}
                                    <div class="row">
                                        <div class="col-md-7">
                                            {% if gamification_stars > 0%}
                                                {% for i in 1..gamification_stars %}
                                                    <i class="fa fa-star"></i>
                                                {% endfor %}
                                            {% endif %}
                                            {% if gamification_stars < 4 %}
                                                {% for i in 1..4 - gamification_stars %}
                                                    <i class="fa fa-star plomo"></i>
                                                {% endfor %}
                                            {% endif %}
                                        </div>
                                        <div class="col-md-5 text-points">
                                            {{ "XPoints"|get_lang|format(gamification_score) }}
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
                    <iframe id="content_id_blank" name="content_name_blank" src="blank.php" border="0" frameborder="0" style="width: 100%; height: 100%; position: absolute;" ></iframe>
                {% else %}
                    <iframe id="content_id" name="content_name" src="{{ iframe_src }}&posts_order=desc" border="0" frameborder="0" style="display: block; width: 100%; height: 100%; position: absolute;"></iframe>
                {% endif %}
                <div class="panel-forum">
                    <div class="open-forum">
                        <i class="fa fa-chevron-up"></i>
                    </div>
                    <div class="closed-forum" style="display: none;">
                        <i class="fa fa-chevron-down"></i>
                    </div>
                    <div id="body-forum">
                        <div id="forum-container">
                            <div class="panel-body"></div>
                        </div>
                    </div>
                </div>
            </div>
            {# end right Zone #}

            {{ navigation_bar_bottom }}
        </div>
    </div>
</div>

<script>


//Function heigth frame content document items
    function updateResizeFrame(){
        var scorm = $('#content_id');

        scorm.load(function() {
            this.style.overflow = 'hidden';
            heightFrame = this.contentWindow.document.body.offsetHeight + 30 + 'px';
            this.style.height = heightFrame;
            $('.panel-forum').css("top", heightFrame);
            $('#body-forum').css("display","none");
        });

    }
    $(document).ready(function() {

        updateResizeFrame();
        $('#touch-button').children().click(function(){
            updateResizeFrame();
        });
        $(window).resize(function() {
            updateResizeFrame();
        });
        $('#forum-container').hide();

        loadForumThead({{ oLP.lp_id }}, {{ oLP.get_current_item_id() }});

    });


    $(document).ready(function(){

        $("#icon-down").click(function(){
            $("#icon-up").removeClass("hidden");
            $(this).addClass("hidden");

            $('#panel-scorm').slideDown("slow",function(){
                updateResizeFrame();
            });
        });

        $("#icon-up").click(function(){
            $("#icon-down").removeClass("hidden");
            $(this).addClass("hidden");
            $('#panel-scorm').slideUp("slow",function(){
                updateResizeFrame();
            });
        });

        $(".scorm-items-accordion li").click(function(){
            updateResizeFrame();
        });
        $(".open-forum").click(function(){
            $('.panel-forum').animate({
                top:"0px",
                height:"100%",
            },800);
            $('#body-forum').css("display","block");
            $(".closed-forum").css("display","block");
            $(".open-forum").css("display","none");
            $("#chamilo-disqus").css("height",heightFrame);
        });
        $(".closed-forum").click(function(){
            $('.panel-forum').animate({
                top:heightFrame,
                height:"100%"
            },800);
            $('#body-forum').css("display","none");
            $(".closed-forum").css("display","none");
            $(".open-forum").css("display","block");
        });
    });
</script>
