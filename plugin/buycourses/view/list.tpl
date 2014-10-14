<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<script>
$(function() {
/* Binds a tab id in the url */
    $("#tabs").bind('tabsselect', function(event, ui) {
        window.location.href=ui.tab;
    });
    // Generate tabs with jquery-ui
    $('#tabs').tabs();
    $( "#sub_tab" ).tabs();
});
</script>

{% if sessionsAreIncluded == "YES" %}
    <div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabs"> <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all"> <li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"> <a href="#tabs-1">{{ 'Courses'|get_lang }}</a></li><li class="ui-state-default ui-corner-top"> <a href="#tabs-2">{{ 'Sessions'|get_lang }}</a></li></ul>
{% endif %}

<div id="tabs-1" class="row">
    <div class="span3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h4>{{ 'SearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}:</h4></li>
                <li class="nav-header">{{ 'CourseName'|get_lang }}:</li>
                <li><input type="text" id="course_name" style="width:95%"/></li>
                <li class="nav-header">{{ 'MinimumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:
                    <input type="text" id="price_min" class="span1"/>
                </li>
                <li class="nav-header">{{ 'MaximumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:
                    <input type="text" id="price_max" class="span1"/>
                </li>
                <li class="nav-header">{{ 'Categories'|get_lang }}:</li>
                <li>
                    <select id="courses_category">
                        <option value="" selected="selected"></option>
                        {% for category in categories %}
                            <option value="{{ category.code }}">{{ category.name }}</option>
                        {% endfor %}
                    </select>
                </li>
                <br />
                <li class="ta-center">
                    <input type="button" class="btn btn-primary" value="{{ 'Search'|get_lang }}" id="confirm_filter" />
                </li>
            </ul>
        </div>
    </div>
    <div class="span8" id="course_results">
        {% if rmessage == "YES" %}
            <div class="{{ class }}">
                {{ responseMessage }}
            </div>
        {% endif %}
        {% for course in courses %}
            <div class="well_border span8">
                <div class="row">
                    <div class="span">
                        <div class="thumbnail">
                            <a class="ajax" rel="gb_page_center[778]" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                <img alt="" src="{{ server }}{{ course.course_img }}">
                            </a>
                        </div>
                    </div>
                    <div class="span4">
                        <div class="categories-course-description">
                            <h3>{{ course.title }}</h3>
                            <h5>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</h5>
                        </div>
                        {% if course.enrolled == "YES" %}
                            <span class="label label-info">{{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                        {% endif %}
                        {% if course.enrolled == "TMP" %}
                            <span class="label label-warning">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                        {% endif %}
                    </div>
                    <div class="span right">
                        <div class="sprice right">
                            {{ course.price }} {{ currency }}
                        </div>
                        <div class="cleared"></div>
                        <div class="btn-toolbar right">
                            <a class="ajax btn btn-primary" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                {{ 'Description'|get_lang }}
                            </a>
                            {% if course.enrolled == "NO" %}
                                <a class="btn btn-success" title="" href="{{ server }}plugin/buycourses/src/process.php?code={{ course.id }}">
                                    {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>
                            {% endif %}
                        </div>
                    </div>
                </div>
            </div>
        {% endfor %}
    </div>
</div>
{% if sessionsAreIncluded == "YES" %}
<div id="tabs-2" class="row">
    <div class="span3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h4>{{ 'SearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}:</h4></li>
                <li class="nav-header">{{ 'SessionName'|get_lang }}:</li>
                <li><input type="text" id="session_name" style="width:95%"/></li>
                <li class="nav-header">{{ 'MinimumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:
                    <input type="text" id="session_price_min" class="span1"/>
                </li>
                <li class="nav-header">{{ 'MaximumPrice'|get_plugin_lang('BuyCoursesPlugin') }}:
                    <input type="text" id="session_price_max" class="span1"/>
                </li>
                <li class="nav-header">{{ 'Categories'|get_lang }}:</li>
                <li>
                    <select id="sessions_category">
                        <option value="" selected="selected"></option>
                        {% for category in categories %}
                            <option value="{{ category.code }}">{{ category.name }}</option>
                        {% endfor %}
                    </select>
                </li>
                <br />
                <li class="ta-center">
                    <input type="button" class="btn btn-primary" value="{{ 'Search'|get_lang }}" id="confirm_session_filter" />
                </li>
            </ul>
        </div>
    </div>
    <div class="span8" id="session_results">
        {% if rmessage == "YES" %}
            <div class="{{ class }}">
                {{ responseMessage }}
            </div>
        {% endif %}
        {% for session in sessions %}
            <div class="well_border span8">
                <div class="row">
                    <div class="span4 ">
                        <div class="categories-course-description">
                            <h3>{{ session.name }}</h3>
                            <h5>{{ 'From'|get_lang }} {{ session.date_start }} {{ 'Until'|get_lang }} {{ session.date_end }}</h5>
                            {% if session.enrolled == "YES" %}
                                <span class="label label-info">{{ 'TheUserIsAlreadyRegisteredInTheSession'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                            {% endif %}
                            {% if session.enrolled == "TMP" %}
                                <span class="label label-warning">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                            {% endif %}
                        </div>
                    </div>
                    <div class="span right">
                        <div class="sprice right">
                            {{ session.price }} {{ currency }}
                        </div>
                        <div class="cleared"></div>
                        <div class="btn-toolbar right">
                            {% if session.enrolled == "NO" %}
                                <a class="btn btn-success" title="" href="{{ server }}plugin/buycourses/src/process.php?scode={{ session.session_id }}">
                                    {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>
                            {% endif %}
                        </div>
                    </div>
                </div>
                {% for course in session.courses %}
                    <div class="row">
                        <div class="span">
                            <div class="thumbnail">
                                <a class="ajax" rel="gb_page_center[778]" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                    <img alt="" src="{{ server }}{{ course.course_img }}">
                                </a>
                            </div>
                        </div>
                        <div class="span4">
                            <div class="categories-course-description">
                                <h3>{{ course.title }}</h3>
                                <h5>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</h5>
                            </div>
                            {% if course.enrolled == "YES" %}
                                <span class="label label-info">{{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                            {% endif %}
                            {% if course.enrolled == "TMP" %}
                                <span class="label label-warning">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                            {% endif %}
                        </div>
                        <div class="span right">
                            <div class="cleared"></div>
                            <div class="btn-toolbar right">
                                <a class="ajax btn btn-primary" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                    {{ 'Description'|get_lang }}
                                </a>
                            </div>
                        </div>
                    </div>
                {% endfor %}
            </div>
        {% endfor %}
    </div>
</div>
</div>
{% endif %}
