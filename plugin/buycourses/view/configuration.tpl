<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

{% if sessions_are_included %}
    <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#courses" aria-controls="courses" role="tab" data-toggle="tab">{{ 'Courses'|get_lang }}</a>
        </li>
        <li role="presentation">
            <a href="#sessions" aria-controls="sessions" role="tab" data-toggle="tab">{{ 'Sessions'|get_lang }}</a>
        </li>
    </ul>
{% endif %}

<div class="tab-content">
    <div role="tabpanel" class="tab-pane fade in active" id="courses">
        <div class="table-responsive">
            <table id="courses_table" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>{{ 'Title'|get_lang }}</th>
                        <th class="text-center">{{ 'OfficialCode'|get_lang }}</th>
                        <th class="text-center">{{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-right" width="200">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-right">{{ 'Options'|get_lang }}</th>
                    </tr>
                </thead>

                <tbody>
                    {% for item in courses %}
                        <tr data-item="{{ item.course_id }}" data-type="course">
                            <td>
                                {% if item.course_visibility == 0 %}
                                    <img src="{{ 'bullet_red.png'|icon() }}" alt="{{ 'CourseVisibilityClosed'|get_lang }}" title="{{ 'CourseVisibilityClosed'|get_lang }}">
                                {% elseif item.course_visibility == 1 %}
                                    <img src="{{ 'bullet_orange.png'|icon() }}" alt="{{ 'Private'|get_lang }}" title="{{ 'Private'|get_lang }}">
                                {% elseif item.course_visibility == 2 %}
                                    <img src="{{ 'bullet_green.png'|icon() }}" alt="{{ 'OpenToThePlatform'|get_lang }}" title="{{ 'OpenToThePlatform'|get_lang }}">
                                {% elseif item.course_visibility == 3 %}
                                    <img src="{{ 'bullet_blue.png'|icon() }}" alt="{{ 'OpenToTheWorld'|get_lang }}" title="{{ 'OpenToTheWorld'|get_lang }}">
                                {% elseif item.course_visibility == 4 %}
                                    <img src="{{ 'bullet_gray.png'|icon() }}" alt="{{ 'CourseVisibilityHidden'|get_lang }}" title="{{ 'CourseVisibilityHidden'|get_lang }}">
                                {% endif %}

                                <a href="{{ _p.web_course ~ item.course_code ~ '/index.php' }}">{{ item.course_title }}</a>
                                <span class="label label-info">{{ item.course_visual_code }}</span>
                            </td>
                            <td class="text-center">
                                {{ item.course_code }}
                            </td>
                            <td class="text-center">
                                {% if item.visible %}
                                    <em class="fa fa-fw fa-check-square-o"></em>
                                {% else %}
                                    <em class="fa fa-fw fa-square-o"></em>
                                {% endif %}
                            </td>
                            <td width="200" class="text-right">
                                {{ "#{item.price} #{tem.currency ?: item.currency}" }}
                            </td>
                            <td class="text-right">
                                <a href="{{ _p.web_plugin ~ 'buycourses/src/configure_course.php?' ~ {'i': item.course_id, 't':product_type_course}|url_encode() }}" class="btn btn-info btn-sm">
                                    <em class="fa fa-wrench fa-fw"></em> {{ 'Configure'|get_lang }}
                                </a>
                            </td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </div>

    {% if sessions_are_included %}
        <div role="tabpanel" class="tab-pane" id="sessions">
            <div class="table-responsive">
                <table id="courses_table" class="table">
                    <thead>
                        <tr>
                            <th>{{ 'Title'|get_lang }}</th>
                            <th class="text-center">{{ 'StartDate'|get_lang }}</th>
                            <th class="text-center">{{ 'EndDate'|get_lang }}</th>
                            <th class="text-center">{{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="text-right">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="text-right">{{ 'Options'|get_lang }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for item in sessions %}
                            <tr data-item="{{ item.session_id }}" data-type="session">
                                <td>
                                    {% if item.session_visibility == 0 %}
                                        <img src="{{ 'bullet_red.png'|icon() }}" alt="{{ 'CourseVisibilityClosed'|get_lang }}" title="{{ 'CourseVisibilityClosed'|get_lang }}">
                                    {% elseif item.session_visibility == 1 %}
                                        <img src="{{ 'bullet_orange.png'|icon() }}" alt="{{ 'Private'|get_lang }}" title="{{ 'Private'|get_lang }}">
                                    {% elseif item.session_visibility == 2 %}
                                        <img src="{{ 'bullet_green.png'|icon() }}" alt="{{ 'OpenToThePlatform'|get_lang }}" title="{{ 'OpenToThePlatform'|get_lang }}">
                                    {% elseif item.session_visibility == 3 %}
                                        <img src="{{ 'bullet_blue.png'|icon() }}" alt="{{ 'OpenToTheWorld'|get_lang }}" title="{{ 'OpenToTheWorld'|get_lang }}">
                                    {% elseif item.session_visibility == 4 %}
                                        <img src="{{ 'bullet_gray.png'|icon() }}" alt="{{ 'CourseVisibilityHidden'|get_lang }}" title="{{ 'CourseVisibilityHidden'|get_lang }}">
                                    {% endif %}

                                    <a href="{{ _p.web_main ~ 'session/index.php?' ~ {'session_id': item.session_id}|url_encode() }}">{{ item.session_name }}</a>
                                </td>
                                <td class="text-center">
                                    {{ item.session_display_start_date }}
                                </td>
                                <td class="text-center">
                                    {{ item.session_display_end_date }}
                                </td>
                                <td class="text-center">
                                    {% if item.visible %}
                                        <em class="fa fa-fw fa-check-square-o"></em>
                                    {% else %}
                                        <em class="fa fa-fw fa-square-o"></em>
                                    {% endif %}
                                </td>
                                <td class="text-right" width="200">
                                    {{ "#{item.price} #{tem.currency ?: item.currency}" }}
                                </td>
                                <td class="text-right">
                                    <a href="{{ _p.web_plugin ~ 'buycourses/src/configure_course.php?' ~ {'i': item.session_id, 't': product_type_session}|url_encode() }}" class="btn btn-info btn-sm">
                                        <em class="fa fa-wrench fa-fw"></em> {{ 'Configure'|get_lang }}
                                    </a>
                                </td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    {% endif %}
</div>
