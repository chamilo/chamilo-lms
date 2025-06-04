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

{% autoescape false %}
<div class="w-full px-4">
    {% if bbb_status %}
    <div class="text-center space-y-4">
        {{ form }}

        {% if show_join_button and can_see_share_link %}
        <a href="{{ conference_url }}" target="_blank"
           class="inline-block bg-primary text-white font-semibold px-6 py-2 rounded-lg shadow hover:opacity-90 transition">
            {{ 'EnterConference'|get_plugin_lang('BBBPlugin') }}
        </a>

        <h3 class="mt-4 text-lg font-semibold text-gray-90">
            {{ 'UrlMeetingToShare'|get_plugin_lang('BBBPlugin') }}
        </h3>

        <div class="flex justify-center items-center gap-2 mt-2">
            <input id="share_button"
                   type="text"
                   value="{{ conference_url }}"
                   readonly
                   class="w-full max-w-xl px-4 py-2 border border-gray-25 rounded-lg shadow-sm text-gray-90 text-sm bg-white">
            <button onclick="copyTextToClipBoard('share_button');"
                    class="px-4 py-2 bg-gray-15 hover:bg-gray-20 rounded-lg text-sm font-medium text-gray-90">
                <i class="fa fa-copy mr-1"></i> {{ 'Copy text' | get_lang }}
            </button>
        </div>

        <p class="mt-2 text-sm text-gray-90">
          <span id="users_online" class="inline-block bg-warning text-warning-button-text px-2 py-1 rounded-full">
            {{ 'XUsersOnLine'| get_plugin_lang('BBBPlugin') | format(users_online) }}
          </span>
        </p>

        {% if max_users_limit > 0 %}
        <p class="text-sm mt-1 text-danger">
            {{ 'MaxXUsersWarning' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}
        </p>
        {% endif %}
        {% elseif max_users_limit > 0 %}
        <p class="text-sm mt-1 text-danger">
            {% if conference_manager %}
            {{ 'MaxXUsersReachedManager' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}
            {% elseif users_online > 0 %}
            {{ 'MaxXUsersReached' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}
            {% endif %}
        </p>
        {% endif %}
    </div>

    <div class="mt-10">
        <h2 class="text-xl font-bold border-b border-gray-25 pb-2 mb-4 text-gray-90">
            {{ 'RecordList'| get_plugin_lang('BBBPlugin') }}
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full border text-sm text-left text-gray-90 bg-white shadow rounded-lg">
                <thead class="bg-gray-15">
                <tr>
                    <th class="px-4 py-2">{{ 'CreatedAt'| get_plugin_lang('BBBPlugin') }}</th>
                    <th class="px-4 py-2">{{ 'Status'| get_lang }}</th>
                    <th class="px-4 py-2">{{ 'Records'| get_plugin_lang('BBBPlugin') }}</th>
                    {% if allow_to_edit %}
                    <th class="px-4 py-2">{{ 'Actions'| get_lang }}</th>
                    {% endif %}
                </tr>
                </thead>
                <tbody>
                {% for meeting in meetings %}
                <tr class="border-t border-gray-25">
                    <td class="px-4 py-2 {% if meeting.visibility == 0 %} text-fontdisabled {% endif %}">
                        {{ meeting.created_at }}
                    </td>
                    <td class="px-4 py-2">
                        {% if meeting.status == 1 %}
                        <span class="inline-block bg-success text-success-button-text px-2 py-1 rounded-full">
                    {{ 'MeetingOpened'|get_plugin_lang('BBBPlugin') }}
                  </span>
                        {% else %}
                        <span class="inline-block bg-info text-info-button-text px-2 py-1 rounded-full">
                    {{ 'MeetingClosed'|get_plugin_lang('BBBPlugin') }}
                  </span>
                        {% endif %}
                    </td>
                    <td class="px-4 py-2">
                        {% if meeting.record == 1 %}
                        {{ meeting.show_links }}
                        {% else %}
                        <span class="text-fontdisabled">{{ 'NoRecording'|get_plugin_lang('BBBPlugin') }}</span>
                        {% endif %}
                    </td>
                    {% if allow_to_edit %}
                    <td class="px-4 py-2 space-x-2">
                        {% if meeting.status == 1 %}
                        <a href="{{ meeting.end_url }}"
                           class="text-danger hover:underline">
                            {{ 'CloseMeeting'|get_plugin_lang('BBBPlugin') }}
                        </a>
                        {% endif %}
                        {{ meeting.action_links }}
                    </td>
                    {% endif %}
                </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
    {% else %}
    <div class="text-center text-danger font-semibold mt-8">
        {{ 'ServerIsNotRunning' | get_plugin_lang('BBBPlugin') }}
    </div>
    {% endif %}
</div>

{% endautoescape %}
