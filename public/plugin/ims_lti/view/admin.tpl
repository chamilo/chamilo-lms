{% import _self as table_tool %}

{% macro row_tool(tool, is_child, categories, _p) %}
    {% set url_params = {'id': tool.id}|url_encode() %}
    <tr class="{{ is_child ? 'child' : '' }}">
        <td {% if is_child %} colspan="2" {% endif %}>
            {{ tool.name }}
            {% if not is_child %}
                <br><span class="badge badge-warning">{{ tool.versionName }}</span>
            {% endif %}
        </td>
        {% if not is_child %}
            <td>{{ tool.launchUrl }}</td>
        {% endif %}
        <td class="text-center">
            {% if tool.course is not empty %}
                {{ tool.course.title }}
            {% endif %}
            {% if tool.session is not empty %}
                ({{ tool.session.name }})
            {% endif %}
        </td>
        <td class="text-right">
            {% if not is_child and tool.version == 'lti1p3' %}
                <a href="{{ _p.web_plugin }}ims_lti/tool_settings.php?{{ url_params }}" class="ajax"
                   data-title="{{ 'ConfigSettingsForTool'|get_plugin_lang('ImsLtiPlugin') }}">
                    {{ 'webservices.png'|img(22, 'ConfigSettingsForTool'|get_plugin_lang('ImsLtiPlugin')) }}
                </a>
            {% endif %}

            {% if not is_child %}
                <a href="{{ _p.web_plugin }}ims_lti/multiply.php?{{ url_params }}">
                    {{ 'multiplicate_survey.png'|img(22, 'AddInCourses'|get_plugin_lang('ImsLtiPlugin')) }}
                </a>
            {% endif %}

            {% if not is_child %}
                <a href="{{ _p.web_plugin }}ims_lti/session.php?{{ url_params }}">
                    {{ 'session.png'|img(22, 'AddInSessions'|get_plugin_lang('ImsLtiPlugin')) }}
                </a>
            {% endif %}

            {% if is_child %}
                {% for category in categories %}
                    {% set url_eval_params = null %}
                    {% if tool.session is not empty %}
                        {% if category.get_course_code == tool.course.code and category.get_session_id == tool.session.id %}
                            {% set url_eval_params = {'selectcat': category.get_id, 'cidReq': tool.course.code, 'id_session': tool.session.id, 'gidReq': 0, 'gradebook': 0}|url_encode() %}
                        {% endif %}
                    {% else %}
                        {% if category.get_course_code == tool.course.code %}
                            {% set url_eval_params = {'selectcat': category.get_id, 'cidReq': tool.course.code, 'id_session': 0, 'gidReq': 0, 'gradebook': 0}|url_encode() %}
                        {% endif %}
                    {% endif %}
                    {% if url_eval_params is not null %}
                        <a href="{{ _p.web_plugin }}ims_lti/gradebook/add_eval.php?{{ url_eval_params }}">
                            {{ 'gradebook.png'|img(22, 'MakeQualifiable'|get_lang) }}
                        </a>
                    {% endif %}
                {% endfor %}
            {% endif %}

            <a href="{{ _p.web_plugin }}ims_lti/edit.php?{{ url_params }}">
                {{ 'edit.png'|img(22, 'Edit'|get_lang) }}
            </a>
            <a href="{{ _p.web_plugin }}ims_lti/delete.php?{{ url_params }}">
                {{ 'delete.png'|img(22, 'Delete'|get_lang) }}
            </a>
        </td>
    </tr>
{% endmacro %}

{{ 'ImsLtiDescription'|get_plugin_lang('ImsLtiPlugin') }}

{% autoescape 'html' %}
    <div class="btn-toolbar">
        <a href="{{ _p.web_plugin }}ims_lti/platform.php" class="btn btn-link pull-right">
            <span class="fa fa-cogs fa-fw" aria-hidden="true"></span> {{ 'PlatformKeys'|get_plugin_lang('ImsLtiPlugin') }}
        </a>
        <a href="{{ _p.web_plugin }}ims_lti/create.php" class="btn btn-primary">
            <span class="fa fa-plus fa-fw" aria-hidden="true"></span> {{ 'AddExternalTool'|get_plugin_lang('ImsLtiPlugin') }}
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover admin-tools">
            <thead>
                <tr>
                    <th>{{ 'Name'|get_lang }}</th>
                    <th>{{ 'LaunchUrl'|get_plugin_lang('ImsLtiPlugin') }}</th>
                    <th class="text-center">{{ 'Course'|get_lang }}</th>
                    <th class="text-right">{{ 'Actions'|get_lang }}</th>
                </tr>
            </thead>
            <tbody>
                {% for tool in tools %}
                    {{ table_tool.row_tool(tool, false, categories, _p) }}
                    {% for child_tool in tool.getChildren %}
                        {{ table_tool.row_tool(child_tool, true, categories, _p) }}
                    {% endfor %}
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endautoescape %}
