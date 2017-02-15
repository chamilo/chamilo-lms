<script>
    function confirmation(name) {
        if (confirm("{{ "AreYouSureToDeleteJS"|get_lang }} \"" + name + "\" ?")) {
            return true;
        } else {
            return false;
        }
    }
</script>

{{ introduction_section }}

{% for lp_data in data %}
    <h3 class="page-header">
        {% if is_allowed_to_edit %}
            {% if (categories|length) > 1 %}
                {{ lp_data.category.getName() }}
            {% endif %}
        {% else %}
            {% if (categories|length) > 1 %}
                {% if lp_data.lp_list is not empty and lp_data.category.getId() != 0 %}
                    {{ lp_data.category.getName() }}
                {% elseif lp_data.lp_list is not empty and lp_data.category.getId() == 0 %}
                    {{ lp_data.category.getName() }}
                {% elseif lp_data.lp_list is not empty and lp_data.category.getId() != 0 %}
                    {{ lp_data.category.getName() }}
                {% endif %}
            {% endif %}
        {% endif %}

        {% if lp_data.category.getId() > 0 and is_allowed_to_edit %}
            <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=add_lp_category&id=' ~ lp_data.category.getId() }}" title="{{ "Edit"|get_lang }}">
                <img src="{{ "edit.png"|icon }}" alt="{{ "Edit"|get_lang }}">
            </a>

            <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=add_users_to_category&id=' ~ lp_data.category.getId() }}" title="{{ "AddUser"|get_lang }}">
                <img src="{{ "user.png"|icon }}" alt="{{ "AddUser"|get_lang }}">
            </a>

            {% if loop.index0 == 1 %}
                <a href="#">
                    <img src="{{ "up_na.png"|icon }}" alt="{{ "Move"|get_lang }}">
                </a>
            {% else %}
                <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=move_up_category&id=' ~ lp_data.category.getId() }}" title="{{ "Move"|get_lang }}">
                    <img src="{{ "up.png"|icon }}" alt="{{ "Move"|get_lang }}">
                </a>
            {% endif %}

            {% if (data|length - 1) == loop.index0 %}
                <a href="#">
                    <img src="{{ "down_na.png"|icon }}" alt="{{ "Move"|get_lang }}">
                </a>
            {% else %}
                <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query ~ '&action=move_down_category&id=' ~ lp_data.category.getId() }}" title="{{ "Move"|get_lang }}">
                    <img src="{{ "down.png"|icon }}" alt="{{ "Move"|get_lang }}">
                </a>
            {% endif %}

            <a href="{{ 'lp_controller.php?' ~ _p.web_cid_query  ~ '&action=delete_lp_category&id=' ~ lp_data.category.getId() }}" title="{{ "Delete"|get_lang }}">
                <img src="{{ "delete.png"|icon }}" alt="{{ "Delete"|get_lang }}">
            </a>
        {% endif %}
    </h3>

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
                            <th>{{ "AuthoringOptions"|get_lang }}</th>
                        {% else %}
                            {% if not is_invitee %}
                                <th>{{ "Progress"|get_lang }}</th>
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
                            {% else %}
                                {% if not is_invitee %}
                                    <td>
                                        {{ row.dsp_progress }}
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
                            </td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    {% endif %}
{% endfor %}

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
