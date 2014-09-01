<script type='text/javascript' src="../js/buycourses.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
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
                    <input type="button" class="btn btn-primary" value="Search Courses" id="confirm_filter" />
                </li>
            </ul>
        </div>
    </div>
    <div class="span9" id="course_results">
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
