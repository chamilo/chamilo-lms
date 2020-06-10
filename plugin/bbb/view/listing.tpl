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
    <div class ="col-md-12" style="text-align:center">
        {{ form }}
        {% if show_join_button == true %}
            {% if show_client_options %}
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default conference">
                            <div class="panel-body">
                                <div class="url">
                                    <a class="btn btn-default" href="{{ enter_conference_links.0.url }}">
                                        <img src="{{ enter_conference_links.0.icon }}" /><br>
                                        {{ enter_conference_links.0.text }}
                                    </a>
                                </div>
                                <div class="share">
                                    {{ 'UrlMeetingToShare'| get_plugin_lang('BBBPlugin') }}
                                </div>
                                <div class="form-inline">
                                    <div class="form-group">
                                        <input id="share_button_flash" type="text"
                                               style="width:300px"
                                               class="form-control" readonly value="{{ conference_url }}&interface=0">
                                        <button onclick="copyTextToClipBoard('share_button_flash');" class="btn btn-default">
                                            <span class="fa fa-copy"></span> {{ 'CopyTextToClipboard' | get_lang }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-default conference">
                            <div class="panel-body">
                                <div class="url">
                                    <a class="btn btn-default" href="{{ enter_conference_links.1.url }}">
                                        <img src="{{ enter_conference_links.1.icon }}" /><br>
                                        {{ enter_conference_links.1.text }}
                                    </a>
                                </div>
                                <div class="share">
                                    {{ 'UrlMeetingToShare'| get_plugin_lang('BBBPlugin') }}
                                </div>
                                <div class="form-inline">
                                    <div class="form-group">
                                        <input id="share_button_html" type="text"
                                               style="width:300px"
                                               class="form-control" readonly value="{{ conference_url }}&interface=1">

                                        <button onclick="copyTextToClipBoard('share_button_html');" class="btn btn-default">
                                            <span class="fa fa-copy"></span> {{ 'CopyTextToClipboard' | get_lang }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {% else %}
                {{ enter_conference_links.0 }}
                <br />
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
            {% endif %}
            <p>
                <span id="users_online" class="label label-warning">
                    {{ 'XUsersOnLine'| get_plugin_lang('BBBPlugin') | format(users_online) }}
                </span>
            </p>
            {{ warning_inteface_msg }}
            {% if max_users_limit > 0 %}
                {% if conference_manager == true %}
                    <p>{{ 'MaxXUsersWarning' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}</p>
                {% elseif users_online >= max_users_limit/2 %}
                    <p>{{ 'MaxXUsersWarning' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}</p>
                {% endif %}
            {% endif %}
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
                    {% if meeting.record == 1 %}
                        {# Record list #}
                        {{ meeting.show_links }}
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
{% else %}
    <div class ="col-md-12" style="text-align:center">
        {{ 'ServerIsNotRunning' | get_plugin_lang('BBBPlugin') | return_message('warning') }}
    </div>
{% endif %}
</div>
