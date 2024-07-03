<style>
    .conference .url{
        padding: 5px;
        margin-bottom: 5px;
    }
    .conference .share{
        padding: 10px;
        margin-top: 5px;
        margin-bottom: 5px;
        font-weight: bold;
    }
</style>
<div class ="row">
{% if bbb_status == true %}
    <div class ="col-md-12">
        {{ form }}
        {% if show_join_button == true %}
            {{ enter_conference_links }}
            <div class="text-center">
                <strong>{{ 'UrlMeetingToShare'| get_plugin_lang('BBBPlugin') }}</strong>
                <div class="well">
                    <div class="form-inline">
                        <div class="form-group">
                            <input id="share_button"
                                   type="text"
                                   style="width:600px"
                                   class="form-control" readonly value="{{ conference_url }}">
                            <button onclick="copyTextToClipBoard('share_button');" class="btn btn-default">
                                <span class="fa fa-copy"></span> {{ 'CopyTextToClipboard' | get_lang }}
                            </button>
                        </div>
                    </div>
                </div>

                <p>
                <span id="users_online" class="label label-warning">
                    {{ 'XUsersOnLine'| get_plugin_lang('BBBPlugin') | format(users_online) }}
                </span>
                </p>

                {% if max_users_limit > 0 %}
                    {% if conference_manager == true %}
                        <p>{{ 'MaxXUsersWarning' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}</p>
                    {% elseif users_online >= max_users_limit/2 %}
                        <p>{{ 'MaxXUsersWarning' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}</p>
                    {% endif %}
                {% endif %}
            </div>
        </div>
        {% elseif max_users_limit > 0 %}
            {% if conference_manager == true %}
                <p>{{ 'MaxXUsersReachedManager' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}</p>
            {% elseif users_online > 0 %}
                <p>{{ 'MaxXUsersReached' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}</p>
            {% endif %}
        {% endif %}
    </div>

    <div class ="col-md-12">
        <div class="page-header">
            <h2>{{ 'RecordList'| get_plugin_lang('BBBPlugin') }}</h2>
        </div>
        <table class="table">
            <tr>
                <th>{{ 'Name'|get_lang }}</th>
                <th>{{ 'CreatedAt'| get_plugin_lang('BBBPlugin') }}</th>
                <th>{{ 'Status'| get_lang }}</th>
                <th>{{ 'Records'| get_plugin_lang('BBBPlugin') }}</th>
                {% if allow_to_edit  %}
                    <th>{{ 'Actions'| get_lang }}</th>
                {% endif %}
            </tr>
            {% for meeting in meetings %}
            <tr>
                <!-- td>{{ meeting.id }}</td -->
                <td>{{ meeting.metting_name }}</td>
                {% if meeting.visibility == 0 %}
                    <td class="muted">{{ meeting.created_at }}</td>
                {% else %}
                    <td>{{ meeting.created_at }}</td>
                {% endif %}
                <td>
                    {% if meeting.status == 1 %}
                        <span class="label label-success">{{ 'MeetingOpened'|get_plugin_lang('BBBPlugin') }}</span>
                    {% else %}
                        <span class="label label-info">{{ 'MeetingClosed'|get_plugin_lang('BBBPlugin') }}</span>
                    {% endif %}
                </td>
                <td>
                    {% if meeting.show_links.record  %}
                        {# Record list #}
                        {% for link in meeting.show_links %}
                            {% if link is not iterable  %}
                            {{ link }}
                            {% endif %}
                        {% endfor %}
                        {% else %}
                            {{ 'NoRecording'|get_plugin_lang('BBBPlugin') }}
                    {% endif %}

                </td>
                {% if allow_to_edit %}
                    <td>
                    {% if meeting.status == 1 %}
                        <a class="btn btn-default" href="{{ meeting.end_url }} ">
                            {{ 'CloseMeeting'|get_plugin_lang('BBBPlugin') }}
                        </a>
                    {% endif %}
                    {{ meeting.action_links }}
                    </td>
                {% endif %}

            </tr>
            {% endfor %}
        </table>
    </div>
    <nav>
        <ul class="pagination">
            <li class="page-item {% if page_id <= 1   %} disabled {% endif %} ">
                <a class="page-link"
                href=  "{{ _p.web_self_query_vars ~ '&' ~ {'page_id' : page_id - 1 }|url_encode() }}"  >
                {{ 'Previous' | get_lang }}
                </a>
            </li>
            {% if page_number > 0 %}
                {% for i in 1..page_number %}
                    <li class="page-item {% if page_id == i %} active {% endif %} ">
                        <a class="page-link" href="{{ _p.web_self_query_vars ~ '&' ~ {'page_id': i  }|url_encode() }}" >
                        {{ i }}
                        </a>
                    </li>
                {% endfor %}
            {% endif %}
            <li class="page-item {% if page_number <= page_id   %} disabled {% endif %} ">
                <a class="page-link" href=  "{{ _p.web_self_query_vars ~ '&' ~ {'page_id' : page_id + 1 }|url_encode() }}">
                {{ 'Next' | get_lang }}
                </a>
            </li>
        </ul>
    </nav>
{% else %}
    <div class ="col-md-12" style="text-align:center">
        {{ 'ServerIsNotRunning' | get_plugin_lang('BBBPlugin') | return_message('warning') }}
    </div>
{% endif %}
</div>
