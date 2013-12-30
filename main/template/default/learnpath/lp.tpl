{% extends app.template_style ~ "/layout/no_layout.tpl" %}
{% block body %}

{% include app.template_style ~ "/learnpath/lp_js.tpl" %}

<div id="learning_path_main">
    {% if api_is_allowed_to_edit %}
        <div class="row">
            <div id="learning_path_breadcrumb_zone" class="col-md-12">
                {% include app.template_style ~ "/layout/breadcrumb.tpl" %}
            </div>
        </div>
    {% endif %}
    <div class="row">
        <div id="learning_path_left_zone" class="col-md-2">
            <div id="header">
                <a href="{{ _p.web_code_path }}lp_controller.php?action=return_to_course_homepage&{{ course_url }}" target="_self" onclick="javascript: window.parent.API.save_asset();">
                    <img src="{{ _p.web_img_path }}lp_arrow.gif" />
                </a>
                {% if api_is_allowed_to_edit %}
                    {% set course_home_url = _p.web_code_path ~ 'newscorm/lp_controller.php?isStudentView=false&action=return_to_course_homepage&' ~ course_url %}
                {% else %}
                    {% set course_home_url = _p.web_code_path ~ 'newscorm/lp_controller.php?action=return_to_course_homepage&' ~ course_url %}
                {% endif %}

                <a class="btn btn-default" href="{{ course_home_url }}" target="_self" onclick="javascript: window.parent.API.save_asset();">
                    {{ 'CourseHomepageLink' | trans }}
                </a>
            </div>

            <div id="toc_id" name="toc_name">
                <div id="learning_path_toc" class="panel panel-default">
                    <div id="scorm_title" class="panel-heading">
                        {{ lp_name }}
                    </div>
                    <div class="panel-body">
                        <ul class="media-list">
                            <li class="media">
                                <a class="pull-left" href="#">
                                    <img class="media-object" src="{{ picture }}">
                                </a>
                                <div class="media-body">
                                    {{ navigation_bar }}
                                    {{ progress_bar }}
                                </div>
                            </li>
                        </ul>
                        {{ author }}

                        {% if mediaplayer %}
                            <div id="lp_media_file">
                                {{ mediaplayer }}
                            </div>
                        {% endif %}

                    </div>
                    <div id="inner_lp_toc" class="list-group" style="overflow: auto;">
                        {{ table_of_contents }}
                    </div>
                </div>
            </div>
        </div>

        <div id="learning_path_right_zone" class="col-md-10" style="height: 100%;overflow: hidden;">
            <div id="learning_path_right_zone2" style="
                overflow: hidden;
                width: 100%;
                height: 100%;
                min-height: 100%;
                background:  #eed3d7;
                display: block; ">
            </div>
            {{ iframe }}
        </div>
    </div>
</div>

{% endblock %}