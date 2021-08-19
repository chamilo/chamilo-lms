<script>
    function confirmation(name) {
        if (confirm("{{ "AreYouSureToDeleteJS"|get_lang }} \"" + name + "\" ?")) {
            return true;
        } else {
            return false;
        }
    }
</script>
{% set configuration = 'lp_category_accordion'|api_get_configuration_value %}
<div class="lp-accordion panel-group" id="lp-accordion" role="tablist" aria-multiselectable="true">
    {% for lp_data in data %}
        {% set show_category = true %}

        {% if filtered_category and filtered_category != lp_data.category.id %}
            {% set show_category = false %}
        {% endif %}

        {% if show_category %}
            {% if configuration == 0 %}
                <!--- old view -->
                {% if categories|length > 1 and lp_data.category.id %}
                    {% if is_allowed_to_edit %}
                        <h3 class="page-header">
                            {{ lp_data.category.getName() | trim }}

                            {% if lp_data.category.sessionId %}
                                {{ session_star_icon }}
                            {% endif %}

                            {% if lp_data.category.getId() > 0 %}
                                {% if lp_data.category.sessionId == _c.session_id %}
                                    <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=add_lp_category&id=' ~ lp_data.category.getId() }}"
                                       title="{{ "Edit"|get_lang }}">
                                        <img src="{{ "edit.png"|icon }}" alt="{{ "Edit"|get_lang }}">
                                    </a>

                                    {% if subscription_settings.allow_add_users_to_lp_category %}
                                        <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=add_users_to_category&id=' ~ lp_data.category.getId() }}"
                                           title="{{ "AddUsers"|get_lang }}">
                                            <img src="{{ "user.png"|icon }}" alt="{{ "AddUsers"|get_lang }}">
                                        </a>
                                    {% endif %}

                                    {% if lp_data.category.sessionId == _c.session_id %}
                                        {% if loop.index0 == 1 or first_session_category == lp_data.category.id %}
                                            <a href="#">
                                                <img src="{{ "up_na.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                            </a>
                                        {% else %}
                                            <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=move_up_category&id=' ~ lp_data.category.getId() }}"
                                               title="{{ "Move"|get_lang }}">
                                                <img src="{{ "up.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                            </a>
                                        {% endif %}

                                        {% if (data|length - 1) == loop.index0 %}
                                            <a href="#">
                                                <img src="{{ "down_na.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                            </a>
                                        {% else %}
                                            <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=move_down_category&id=' ~ lp_data.category.getId() }}"
                                               title="{{ "Move"|get_lang }}">
                                                <img src="{{ "down.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                            </a>
                                        {% endif %}
                                    {% endif %}
                                {% endif %}

{#                                {% if lp_data.category.sessionId == _c.session_id %}#}
                                    {% if lp_data.category_visibility == 0 %}
                                        <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_visibility', 'id':lp_data.category.id, 'new_status':1}|url_encode }}"
                                           title="{{ 'Show'|get_lang }}">
                                            <img src="{{ 'invisible.png'|icon }}" alt="{{ 'Show'|get_lang }}">
                                        </a>
                                    {% else %}
                                        <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_visibility', 'id':lp_data.category.id, 'new_status':0}|url_encode }}"
                                           title="{{ 'Hide'|get_lang }}">
                                            <img src="{{ 'visible.png'|icon }}" alt="{{ 'Hide'|get_lang }}">
                                        </a>
                                    {% endif %}
{#                                {% endif %}#}

{#                                {% if not _c.session_id %}#}
                                    {% if lp_data.category_is_published == 0 %}
                                        <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_publish', 'id':lp_data.category.id, 'new_status':1}|url_encode }}"
                                           title="{{ 'LearnpathPublish'|get_lang }}">
                                            <img src="{{ 'lp_publish_na.png'|icon }}"
                                                 alt="{{ 'LearnpathPublish'|get_lang }}">
                                        </a>
                                    {% else %}
                                        <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_publish', 'id':lp_data.category.id, 'new_status':0}|url_encode }}"
                                           title="{{ 'LearnpathDoNotPublish'|get_lang }}">
                                            <img src="{{ 'lp_publish.png'|icon }}" alt="{{ 'Hide'|get_lang }}">
                                        </a>
                                    {% endif %}
{#                                {% endif %}#}

                                {% if lp_data.category.sessionId == _c.session_id %}
                                    <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query  ~ '&action=delete_lp_category&id=' ~ lp_data.category.getId() }}"
                                       title="{{ "Delete"|get_lang }}">
                                        <img src="{{ "delete.png"|icon }}" alt="{{ "Delete"|get_lang }}">
                                    </a>
                                {% endif %}
                            {% endif %}
                        </h3>
                    {% elseif lp_data.lp_list is not empty %}
                        <h3 class="page-header">{{ lp_data.category.getName() }}</h3>
                    {% endif %}
                {% endif %}

                {% if lp_data.lp_list %}
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                            <tr>
                                <th>
                                    {{ "Title"|get_lang }}
                                </th>
                                {% if is_allowed_to_edit %}
                                    <th>{{ "PublicationDate"|get_lang }}</th>
                                    <th>{{ "ExpirationDate"|get_lang }}</th>
                                    <th>{{ "Progress"|get_lang }}</th>
                                    {% if allow_min_time %}
                                        <th>{{ "TimeSpentTimeRequired"|get_lang }}</th>
                                    {% endif %}
                                    <th>{{ "AuthoringOptions"|get_lang }}</th>
                                {% else %}
                                    {% if allow_dates_for_student %}
                                        <th>{{ "PublicationDate"|get_lang }}</th>
                                        <th>{{ "ExpirationDate"|get_lang }}</th>
                                    {% endif %}
                                    {% if not is_invitee %}
                                        <th>{{ "Progress"|get_lang }}</th>
                                    {% endif %}
                                    {% if allow_min_time %}
                                        <th>{{ "TimeSpentTimeRequired"|get_lang }}</th>
                                    {% endif %}
                                    <th>{{ "Actions"|get_lang }}</th>
                                {% endif %}
                            </tr>
                            </thead>
                            <tbody>
                            {% for row in lp_data.lp_list %}
                                <tr>
                                    <td>
                                        {{ row.learnpath_icon }}
                                        <a href="{{ row.url_start }}">
                                            {{ row.title }}
                                            {{ row.session_image }}
                                        </a>
                                        {{ row.extra }}
                                    </td>
                                    {% if is_allowed_to_edit %}
                                        <td>
                                            {% if row.start_time %}
                                                <span class="small">{{ row.start_time }}</span>
                                            {% endif %}
                                        </td>
                                        <td>
                                            <span class="small">{{ row.end_time }}</span>
                                        </td>
                                        <td>
                                            {{ row.dsp_progress }}
                                        </td>
                                        {% if allow_min_time %}
                                            <td>
                                            {% if row.info_time_prerequisite %}
                                                {{ row.info_time_prerequisite }}
                                            {% endif %}
                                            </td>
                                        {% endif %}
                                    {% else %}
                                        {% if allow_dates_for_student %}
                                            <td>
                                                {% if row.start_time %}
                                                    <span class="small">{{ row.start_time }}</span>
                                                {% endif %}
                                            </td>
                                            <td>
                                                <span class="small">{{ row.end_time }}</span>
                                            </td>
                                        {% endif %}
                                        {% if not is_invitee %}
                                            <td>
                                                {{ row.dsp_progress }}
                                            </td>
                                        {% endif %}
                                        {% if allow_min_time %}
                                            <td>
                                                {% if row.info_time_prerequisite %}
                                                    {{ row.info_time_prerequisite }}
                                                {% endif %}
                                            </td>
                                        {% endif %}
                                    {% endif %}
                                    <td>
                                        {{ row.action_build }}
                                        {{ row.action_edit }}
                                        {{ row.action_visible }}
                                        {{ row.action_tracking }}
                                        {{ row.action_publish }}
                                        {{ row.action_subscribe_users }}
                                        {{ row.action_serious_game }}
                                        {{ row.action_reinit }}
                                        {{ row.action_default_view }}
                                        {{ row.action_debug }}
                                        {{ row.action_export }}
                                        {{ row.action_copy }}
                                        {{ row.action_auto_launch }}
                                        {{ row.action_pdf }}
                                        {{ row.action_delete }}
                                        {{ row.action_order }}
                                        {{ row.action_update_scorm }}
                                        {{ row.action_export_to_course_build }}
                                    </td>
                                </tr>
                            {% endfor %}
                            </tbody>
                        </table>
                    </div>
                {% endif %}
                <!--- end old view -->
            {% else %}
                <!-- new view block accordeon -->
                {% if lp_data.category.id == 0 %}
                    {% if is_allowed_to_edit %}
                        {% if lp_data.lp_list %}
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                    <tr>
                                        <th>{{ "Title"|get_lang }}</th>
                                        {% if is_allowed_to_edit %}
                                            <th>{{ "PublicationDate"|get_lang }}</th>
                                            <th>{{ "ExpirationDate"|get_lang }}</th>
                                            <th>{{ "Progress"|get_lang }}</th>
                                            {% if allow_min_time %}
                                                <th>{{ "TimeSpentTimeRequired"|get_lang }}</th>
                                            {% endif %}
                                            <th>{{ "AuthoringOptions"|get_lang }}</th>
                                        {% else %}
                                            {% if allow_dates_for_student %}
                                                <th>{{ "PublicationDate"|get_lang }}</th>
                                                <th>{{ "ExpirationDate"|get_lang }}</th>
                                            {% endif %}
                                            {% if not is_invitee %}
                                                <th>{{ "Progress"|get_lang }}</th>
                                            {% endif %}
                                            {% if allow_min_time %}
                                                <th>{{ "TimeSpentTimeRequired"|get_lang }}</th>
                                            {% endif %}
                                            <th>{{ "Actions"|get_lang }}</th>
                                        {% endif %}
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {% for row in lp_data.lp_list %}
                                        <tr>
                                            <td>
                                                {{ row.learnpath_icon }}
                                                <a href="{{ row.url_start }}">
                                                    {{ row.title }}
                                                    {{ row.session_image }}
                                                    {{ row.extra }}
                                                </a>
                                            </td>
                                            {% if is_allowed_to_edit %}
                                                <td>
                                                    {% if row.start_time %}
                                                        <span class="small">{{ row.start_time }}</span>
                                                    {% endif %}
                                                </td>
                                                <td>
                                                    <span class="small">{{ row.end_time }}</span>
                                                </td>
                                                <td>
                                                    {{ row.dsp_progress }}
                                                </td>
                                                {% if allow_min_time %}
                                                    <td>
                                                        {% if row.info_time_prerequisite %}
                                                            {{ row.info_time_prerequisite }}
                                                        {% endif %}
                                                    </td>
                                                {% endif %}
                                            {% else %}
                                                {% if allow_dates_for_student %}
                                                    <td>
                                                        {% if row.start_time %}
                                                            <span class="small">{{ row.start_time }}</span>
                                                        {% endif %}
                                                    </td>
                                                    <td>
                                                        <span class="small">{{ row.end_time }}</span>
                                                    </td>
                                                {% endif %}
                                                {% if not is_invitee %}
                                                    <td>
                                                        {{ row.dsp_progress }}
                                                    </td>
                                                {% endif %}
                                                {% if allow_min_time %}
                                                    <td>
                                                        {% if row.info_time_prerequisite %}
                                                            {{ row.info_time_prerequisite }}
                                                        {% endif %}
                                                    </td>
                                                {% endif %}
                                            {% endif %}
                                            <td>
                                                {{ row.action_build }}
                                                {{ row.action_edit }}
                                                {{ row.action_visible }}
                                                {{ row.action_tracking }}
                                                {{ row.action_publish }}
                                                {{ row.action_subscribe_users }}
                                                {{ row.action_serious_game }}
                                                {{ row.action_reinit }}
                                                {{ row.action_default_view }}
                                                {{ row.action_debug }}
                                                {{ row.action_export }}
                                                {{ row.action_copy }}
                                                {{ row.action_auto_launch }}
                                                {{ row.action_pdf }}
                                                {{ row.action_delete }}
                                                {{ row.action_order }}
                                                {{ row.action_update_scorm }}
                                                {{ row.action_export_to_course_build }}
                                            </td>
                                        </tr>
                                    {% endfor %}
                                    </tbody>
                                </table>
                            </div>
                        {% endif %}
                    {% else %}
                        <div id="not-category" class="panel panel-default">
                            <div class="panel-body">
                                {% for row in lp_data.lp_list %}
                                    <div class="lp-item">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <i class="fa fa-chevron-circle-right" aria-hidden="true"></i>
                                                <a href="{{ row.url_start }}">
                                                    {{ row.title }}
                                                    {{ row.session_image }}
                                                    {{ row.extra }}
                                                </a>
                                            </div>
                                            <div class="col-md-3">
                                                {{ row.dsp_progress }}
                                            </div>

                                            {% if allow_dates_for_student %}
                                                <div class="col-md-2">
                                                    {% if row.start_time %}
                                                        <span class="small">{{ row.start_time }}</span>
                                                    {% endif %}
                                                    <span class="small">{{ row.end_time }}</span>
                                                </div>
                                            {% endif %}

                                            {% if allow_min_time %}
                                                <div class="col-md-2">
                                                    {% if row.info_time_prerequisite %}
                                                        {{ row.info_time_prerequisite }}
                                                    {% endif %}
                                                </div>
                                            {% endif %}
                                            <div class="col-md-1">
                                                {{ row.action_pdf }}
                                                {{ row.action_export }}
                                            </div>
                                        </div>
                                    </div>
                                {% endfor %}
                            </div>
                        </div>
                    {% endif %}
                {% endif %}

                {% if categories|length > 1 and lp_data.category.id %}
                    {% set number = number + 1 %}
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="heading-{{ lp_data.category.getId() }}">
                            {% if is_allowed_to_edit %}
                                <div class="tools-actions pull-right">
                                    {% if lp_data.category.getId() > 0 %}
{#                                      {% if not _c.session_id %}#}
                                        {% if lp_data.category.sessionId == _c.session_id %}
                                            <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=add_lp_category&id=' ~ lp_data.category.getId() }}"
                                               title="{{ "Edit"|get_lang }}">
                                                <img src="{{ "edit.png"|icon }}" alt="{{ "Edit"|get_lang }}">
                                            </a>

                                            {% if subscription_settings.allow_add_users_to_lp_category %}
                                                <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=add_users_to_category&id=' ~ lp_data.category.getId() }}"
                                                   title="{{ "AddUsers"|get_lang }}">
                                                    <img src="{{ "user.png"|icon }}" alt="{{ "AddUsers"|get_lang }}">
                                                </a>
                                            {% endif %}

                                            {% if loop.index0 == 1 %}
                                                <a href="#">
                                                    <img src="{{ "up_na.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                                </a>
                                            {% else %}
                                                <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=move_up_category&id=' ~ lp_data.category.getId() }}"
                                                   title="{{ "Move"|get_lang }}">
                                                    <img src="{{ "up.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                                </a>
                                            {% endif %}

                                            {% if (data|length - 1) == loop.index0 %}
                                                <a href="#">
                                                    <img src="{{ "down_na.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                                </a>
                                            {% else %}
                                                <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=move_down_category&id=' ~ lp_data.category.getId() }}"
                                                   title="{{ "Move"|get_lang }}">
                                                    <img src="{{ "down.png"|icon }}" alt="{{ "Move"|get_lang }}">
                                                </a>
                                            {% endif %}
                                        {% endif %}

                                        {% if lp_data.category_visibility == 0 %}
                                            <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_visibility', 'id':lp_data.category.id, 'new_status':1}|url_encode }}"
                                               title="{{ 'Show'|get_lang }}">
                                                <img src="{{ 'invisible.png'|icon }}" alt="{{ 'Show'|get_lang }}">
                                            </a>
                                        {% else %}
                                            <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_visibility', 'id':lp_data.category.id, 'new_status':0}|url_encode }}"
                                               title="{{ 'Hide'|get_lang }}">
                                                <img src="{{ 'visible.png'|icon }}" alt="{{ 'Hide'|get_lang }}">
                                            </a>
                                        {% endif %}

                                        {% if lp_data.category_visibility == 1 %}
                                            {% if lp_data.category_is_published == 0 %}
                                                <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_publish', 'id':lp_data.category.id, 'new_status':1}|url_encode }}"
                                                   title="{{ 'LearnpathPublish'|get_lang }}">
                                                    <img src="{{ 'lp_publish_na.png'|icon }}"
                                                         alt="{{ 'LearnpathPublish'|get_lang }}">
                                                </a>
                                            {% else %}
                                                <a href="lp_controller.php?{{ _p.web_cid_query ~ '&' ~ {'action':'toggle_category_publish', 'id':lp_data.category.id, 'new_status':0}|url_encode }}"
                                                   title="{{ 'LearnpathDoNotPublish'|get_lang }}">
                                                    <img src="{{ 'lp_publish.png'|icon }}" alt="{{ 'Hide'|get_lang }}">
                                                </a>
                                            {% endif %}
                                        {% else %}
                                            <img src="{{ 'lp_publish_na.png'|icon }}"
                                                 alt="{{ 'LearnpathPublish'|get_lang }}">
                                        {% endif %}

                                        {% if not _c.session_id %}
                                            <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query  ~ '&action=delete_lp_category&id=' ~ lp_data.category.getId() }}"
                                               title="{{ "Delete"|get_lang }}">
                                                <img src="{{ "delete.png"|icon }}" alt="{{ "Delete"|get_lang }}">
                                            </a>
                                        {% endif %}
                                    {% endif %}
                                </div>
                            {% endif %}
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#lp-accordion"
                                   href="#collapse-{{ lp_data.category.getId() }}" aria-expanded="{{ number == 1 ? 'true' : 'false' }}"
                                   aria-controls="collapse-{{ lp_data.category.getId() }}">
                                    {{ lp_data.category.getName() }}

                                    {% if lp_data.category.sessionId %}
                                        {{ session_star_icon }}
                                    {% endif %}
                                </a>
                            </h4>
                        </div>
                        <div id="collapse-{{ lp_data.category.getId() }}" class="panel-collapse collapse {{ (number == 1 ? 'in':'') }}"
                             role="tabpanel" aria-labelledby="heading-{{ lp_data.category.getId() }}">
                            <div class="panel-body">
                                {% if lp_data.lp_list %}
                                    {% if is_allowed_to_edit %}
                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped">
                                                <thead>
                                                <tr>
                                                    <th>{{ "Title"|get_lang }}</th>
                                                    {% if is_allowed_to_edit %}
                                                        <th>{{ "PublicationDate"|get_lang }}</th>
                                                        <th>{{ "ExpirationDate"|get_lang }}</th>
                                                        <th>{{ "Progress"|get_lang }}</th>
                                                        {% if allow_min_time %}
                                                            <th>{{ "TimeSpentTimeRequired"|get_lang }}</th>
                                                        {% endif %}
                                                        <th>{{ "AuthoringOptions"|get_lang }}</th>
                                                    {% else %}
                                                        {% if allow_dates_for_student %}
                                                            <th>{{ "PublicationDate"|get_lang }}</th>
                                                            <th>{{ "ExpirationDate"|get_lang }}</th>
                                                        {% endif %}
                                                        {% if not is_invitee %}
                                                            <th>{{ "Progress"|get_lang }}</th>
                                                        {% endif %}
                                                        {% if allow_min_time %}
                                                            <th>{{ "TimeSpentTimeRequired"|get_lang }}</th>
                                                        {% endif %}

                                                        <th>{{ "Actions"|get_lang }}</th>
                                                    {% endif %}
                                                </tr>
                                                </thead>
                                                <tbody>
                                                {% for row in lp_data.lp_list %}
                                                    <tr>
                                                        <td>
                                                            {{ row.learnpath_icon }}
                                                            <a href="{{ row.url_start }}">
                                                                {{ row.title }}
                                                                {{ row.session_image }}
                                                                {{ row.extra }}
                                                            </a>
                                                        </td>
                                                        {% if is_allowed_to_edit %}
                                                            <td>
                                                                {% if row.start_time %}
                                                                    <span class="small">{{ row.start_time }}</span>
                                                                {% endif %}
                                                            </td>
                                                            <td>
                                                                <span class="small">{{ row.end_time }}</span>
                                                            </td>
                                                            <td>
                                                                {{ row.dsp_progress }}
                                                            </td>
                                                            {% if allow_min_time %}
                                                                <td>
                                                                    {% if row.info_time_prerequisite %}
                                                                        {{ row.info_time_prerequisite }}
                                                                    {% endif %}
                                                                </td>
                                                            {% endif %}
                                                        {% else %}
                                                            {% if allow_dates_for_student %}
                                                                <td>
                                                                    {% if row.start_time %}
                                                                        <span class="small">{{ row.start_time }}</span>
                                                                    {% endif %}
                                                                </td>
                                                                <td>
                                                                    <span class="small">{{ row.end_time }}</span>
                                                                </td>
                                                            {% endif %}
                                                            {% if not is_invitee %}
                                                                <td>
                                                                    {{ row.dsp_progress }}
                                                                </td>
                                                            {% endif %}
                                                            {% if allow_min_time %}
                                                                <td>
                                                                    {% if row.info_time_prerequisite %}
                                                                        {{ row.info_time_prerequisite }}
                                                                    {% endif %}
                                                                </td>
                                                            {% endif %}
                                                        {% endif %}
                                                        <td>
                                                            {{ row.action_build }}
                                                            {{ row.action_edit }}
                                                            {{ row.action_visible }}
                                                            {{ row.action_tracking }}
                                                            {{ row.action_publish }}
                                                            {{ row.action_subscribe_users }}
                                                            {{ row.action_serious_game }}
                                                            {{ row.action_reinit }}
                                                            {{ row.action_default_view }}
                                                            {{ row.action_debug }}
                                                            {{ row.action_export }}
                                                            {{ row.action_copy }}
                                                            {{ row.action_auto_launch }}
                                                            {{ row.action_pdf }}
                                                            {{ row.action_delete }}
                                                            {{ row.action_order }}
                                                            {{ row.action_update_scorm }}
                                                            {{ row.action_export_to_course_build }}
                                                        </td>
                                                    </tr>
                                                {% endfor %}
                                                </tbody>
                                            </table>
                                        </div>
                                    {% else %}
                                        {% for row in lp_data.lp_list %}
                                            <div class="lp-item">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <i class="fa fa-chevron-circle-right" aria-hidden="true"></i>
                                                        <a href="{{ row.url_start }}">
                                                            {{ row.title }}
                                                            {{ row.session_image }}
                                                            {{ row.extra }}
                                                        </a>
                                                    </div>
                                                    <div class="col-md-3">
                                                        {{ row.dsp_progress }}
                                                    </div>

                                                    {% if allow_dates_for_student %}
                                                        <div class="col-md-2">
                                                            {% if row.start_time %}
                                                                <span class="small">{{ row.start_time }}</span>
                                                            {% endif %}
                                                            <span class="small">{{ row.end_time }}</span>
                                                        </div>
                                                    {% endif %}

                                                    {% if allow_min_time %}
                                                        <div class="col-md-2">
                                                            {% if row.info_time_prerequisite %}
                                                                {{ row.info_time_prerequisite }}
                                                            {% endif %}
                                                        </div>
                                                    {% endif %}
                                                    <div class="col-md-1">
                                                        {{ row.action_pdf }}
                                                        {{ row.action_export }}
                                                    </div>
                                                </div>
                                            </div>
                                        {% endfor %}
                                    {% endif %}
                                {% endif %}
                            </div>
                        </div>
                    </div>
                {% endif %}
                <!-- end view block accordeon -->
            {% endif %}
        {% endif %}
    {% endfor %}
</div>

{% if not is_invitee and lp_is_shown and allow_min_time %}
    <div id="lp_notification_control" class="controls text-center">
        {% if not is_ending %}
            <button class="btn btn-primary" type="button" disabled>
                {{ 'IHaveFinishedTheLessonsNotifyTheTeacher'|get_lang }}
            </button>
        {% else %}
            <a href="{{ web_self ~ "?" ~ _p.web_cid_query ~ "&action=send_notify_teacher" }}" class="btn btn-primary">
                {{ 'IHaveFinishedTheLessonsNotifyTheTeacher'|get_lang }}
            </a>
        {% endif %}
    </div>
{% endif %}

{% if not is_invitee and lp_is_shown and allow_min_time and is_ending %}
    <div id="lp_download_file_after_finish" class="controls text-center">
        {{ download_files_after_finish }}
    </div>
{% endif %}

{% if is_allowed_to_edit and not lp_is_shown %}
    <div id="no-data-view">
        <h2>{{ "LearningPaths"|get_lang }}</h2>
        <img src="{{ "scorms.png"|icon(64) }}" width="64" height="64">
        <div class="controls">
            <a href="{{ web_self ~ "?" ~ _p.web_cid_query ~ "&action=add_lp" }}" class="btn btn-default">
                {{ "LearnpathAddLearnpath"|get_lang }}
            </a>
        </div>
    </div>
{% endif %}
