{% if sessions_are_included or services_are_included %}
    <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
        <li role="presentation" class="{{ courses ? 'active' : '' }}">
            <a href="{{ _p.web_plugin ~ 'buycourses/src/list.php' }}" >
                {{ 'Courses'|get_lang }}
            </a>
        </li>
        {% if sessions_are_included %}
        <li role="presentation" class="{{ sessions ? 'active' : '' }}">
            <a href="{{ _p.web_plugin ~ 'buycourses/src/list_session.php' }}" >
                {{ 'Sessions'|get_lang }}</a>
        </li>
        {% endif %}
        {% if services_are_included %}
            <li role="presentation" class="{{ services ? 'active' : '' }}">
                <a href="{{ _p.web_plugin ~ 'buycourses/src/list_service.php' }}">
                    {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </li>
        {% endif %}
    </ul>
{% endif %}

<div class="tab-content">
    <div role="tabpanel" class="tab-pane {{ courses ? 'fade in active' : '' }} " id="courses">
        <div class="table-responsive">
            <table id="courses_table" class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>{{ 'Title'|get_lang }}</th>
                    <th class="text-center">{{ 'OfficialCode'|get_lang }}</th>
                    <th class="text-center">{{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-right" width="200">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 2) %}
                        <th class="text-center" width="100">{{ tax_name }}</th>
                    {% endif %}
                    <th class="text-right">{{ 'Options'|get_lang }}</th>
                </tr>
                </thead>
                <tbody>
                {% for item in courses %}
                    <tr data-item="{{ item.id }}" data-type="course">
                        <td>
                            {% if item.visibility == 0 %}
                                <img src="{{ 'bullet_red.png'|icon() }}" alt="{{ 'CourseVisibilityClosed'|get_lang }}"
                                     title="{{ 'CourseVisibilityClosed'|get_lang }}">
                            {% elseif item.visibility == 1 %}
                                <img src="{{ 'bullet_orange.png'|icon() }}" alt="{{ 'Private'|get_lang }}"
                                     title="{{ 'Private'|get_lang }}">
                            {% elseif item.visibility == 2 %}
                                <img src="{{ 'bullet_green.png'|icon() }}" alt="{{ 'OpenToThePlatform'|get_lang }}"
                                     title="{{ 'OpenToThePlatform'|get_lang }}">
                            {% elseif item.visibility == 3 %}
                                <img src="{{ 'bullet_blue.png'|icon() }}" alt="{{ 'OpenToTheWorld'|get_lang }}"
                                     title="{{ 'OpenToTheWorld'|get_lang }}">
                            {% elseif item.visibility == 4 %}
                                <img src="{{ 'bullet_grey.png'|icon() }}" alt="{{ 'CourseVisibilityHidden'|get_lang }}"
                                     title="{{ 'CourseVisibilityHidden'|get_lang }}">
                            {% endif %}
                            <a href="{{ _p.web_course ~ item.path ~ item.code~ '/index.php' }}">
                                {{ item.title }}
                            </a>
                            <span class="label label-info">{{ item.code }}</span>
                        </td>
                        <td class="text-center">
                            {{ item.code }}
                        </td>
                        <td class="text-center">
                            {% if item.buyCourseData %}
                                <em class="fa fa-fw fa-check-square-o"></em>
                            {% else %}
                                <em class="fa fa-fw fa-square-o"></em>
                            {% endif %}
                        </td>
                        <td width="200" class="text-right">
                            {{ item.buyCourseData.price_formatted }}
                        </td>
                        {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 2) %}
                            <td class="text-center">
                                {{ item.buyCourseData.tax_perc_show }} %
                            </td>
                        {% endif %}
                        <td class="text-right">
                            <a href="{{ _p.web_plugin ~ 'buycourses/src/configure_course.php?' ~ {'id': item.id, 'type':product_type_course}|url_encode() }}"
                               class="btn btn-info btn-sm">
                                <em class="fa fa-wrench fa-fw"></em> {{ 'Configure'|get_lang }}
                            </a>
                        </td>
                    </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
        {{ course_pagination }}
    </div>

    {% if sessions_are_included %}
        <div role="tabpanel" class="tab-pane {{ sessions ? 'fade in active' : '' }} " id="sessions">
            <div class="table-responsive">
                <table id="session_table" class="table">
                    <thead>
                    <tr>
                        <th>{{ 'Title'|get_lang }}</th>
                        <th class="text-center">{{ 'StartDate'|get_lang }}</th>
                        <th class="text-center">{{ 'EndDate'|get_lang }}</th>
                        <th class="text-center">{{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-right">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 3) %}
                            <th class="text-center" width="100">{{ tax_name }}</th>
                        {% endif %}
                        <th class="text-right">{{ 'Options'|get_lang }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {% for item in sessions %}
                        <tr data-item="{{ item.id }}" data-type="session">
                            <td>
                                <a href="{{ _p.web_main ~ 'session/index.php?' ~ {'session_id': item.id}|url_encode() }}">{{ item.name }}</a>
                            </td>
                            <td class="text-center">
                                {{ item.displayStartDate | api_convert_and_format_date(6)}}
                            </td>
                            <td class="text-center">
                                {{ item.displayEndDate |api_convert_and_format_date(6)}}
                            </td>
                            <td class="text-center">
                                {% if item.buyCourseData %}
                                    <em class="fa fa-fw fa-check-square-o"></em>
                                {% else %}
                                    <em class="fa fa-fw fa-square-o"></em>
                                {% endif %}
                            </td>
                            <td class="text-right" width="200">
                                {{ item.buyCourseData.price_formatted }}
                            </td>
                            {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 3) %}
                                <td class="text-center">
                                    {{ item.buyCourseData.tax_perc_show }} %
                                </td>
                            {% endif %}
                            <td class="text-right">
                                <a href="{{ _p.web_plugin ~ 'buycourses/src/configure_course.php?' ~ {'id': item.id, 'type': product_type_session}|url_encode() }}"
                                   class="btn btn-info btn-sm">
                                    <em class="fa fa-wrench fa-fw"></em>
                                    {{ 'Configure'|get_lang }}
                                </a>
                            </td>
                        </tr>
                    {% endfor %}
                    </tbody>
                </table>
                {{ session_pagination }}
            </div>
        </div>
    {% endif %}
    {% if services_are_included %}
        <a href="{{ _p.web_plugin ~ 'buycourses/src/services_add.php' }}" class="btn btn-primary">
            <em class="fa fa-cart-plus fa-fw"></em> {{ 'NewService'| get_plugin_lang('BuyCoursesPlugin') }}
        </a>
        <div role="tabpanel" class="tab-pane {{ services ? 'fade in active' : '' }} " id="services">
            <div class="table-responsive">
                </br>
                </br>
                <table id="services_table" class="table">
                    <thead>
                    <tr>
                        <th>{{ 'Service'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th>{{ 'Description'|get_lang }}</th>
                        <th class="text-center">{{ 'Duration'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-center">{{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-center">{{ 'Owner'|get_lang }}</th>
                        <th class="text-right">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 4) %}
                            <th class="text-center" width="100">{{ tax_name }}</th>
                        {% endif %}
                        <th class="text-right">{{ 'Options'|get_lang }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {% for item in services %}
                        <tr data-item="{{ item.id }}" data-type="service">
                            <td>
                                {{ item.name }}
                            </td>
                            <td>
                                {{ item.description }}
                            </td>
                            <td class="text-center">
                                {% if item.duration_days == 0 %}
                                    {{ 'NoLimit'|get_lang }}
                                {% else %}
                                    {{ item.duration_days }} {{ 'Days'|get_lang }}
                                {% endif %}
                            </td>
                            <td class="text-center">
                                {% if item.visibility == 1 %}
                                    <em class="fa fa-fw fa-check-square-o"></em>
                                {% else %}
                                    <em class="fa fa-fw fa-square-o"></em>
                                {% endif %}
                            </td>
                            <td class="text-center">
                                {{ item.owner_name }}
                            </td>
                            <td class="text-right" width="200">
                                {{ item.price_formatted }}
                            </td>
                            {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 4) %}
                                <td class="text-center">
                                    {% if item.tax_perc is null %}
                                        {{ global_tax_perc }} %
                                    {% else %}
                                        {{ item.tax_perc }} %
                                    {% endif %}
                                </td>
                            {% endif %}
                            <td class="text-right">
                                <a href="{{ _p.web_plugin ~ 'buycourses/src/services_edit.php?' ~ {'id': item.id}|url_encode() }}"
                                   class="btn btn-info btn-sm">
                                    <em class="fa fa-wrench fa-fw"></em> {{ 'Edit'|get_lang }}
                                </a>
                            </td>
                        </tr>
                    {% endfor %}
                    </tbody>
                </table>
            </div>
            {{ service_pagination }}
        </div>
    {% endif %}
</div>
