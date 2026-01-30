<style>
    .conference-start {
        max-width: 600px;
        margin: 0 auto 1.5rem;
        padding: 1rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }
    #preupload-documents summary {
        font-weight: 600;
        cursor: pointer;
    }
    #preupload-list label {
        display: block;
        margin-bottom: .5rem;
    }
    .users-online {
        text-align: center;
        margin-bottom: 1.5rem;
    }
</style>

{% autoescape false %}
<div class="w-full px-4">
    {% if bbb_status %}
    {% if form %}
    <div class="conference-start">
        {{ form|raw }}
        {% if show_join_button %}
        <div class="mt-4 text-center">
            {% for link in enter_conference_links %}
            {{ link|raw }}
            {% endfor %}
        </div>
        {% endif %}
    </div>
    {% endif %}
    {% if not form and show_join_button %}
    <div class="text-center mb-6">
        {% for link in enter_conference_links %}
        {{ link|raw }}
        {% endfor %}
    </div>
    {% endif %}
    {% if show_join_button and can_see_share_link %}
    <h3 class="text-center text-lg font-semibold mb-2">
        {{ 'UrlMeetingToShare'|get_plugin_lang('BBBPlugin') }}
    </h3>
    <div class="flex justify-center items-center gap-2 mb-6">
        <input id="share_button" type="text" value="{{ conference_url }}" readonly
               class="w-full max-w-md px-4 py-2 border rounded shadow-sm text-sm">
        <button onclick="copyTextToClipBoard('share_button');"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm">
            <i class="fa fa-copy mr-1"></i>{{ 'Copy text'|get_lang }}
        </button>
    </div>
    {% endif %}

    <div class="users-online">
      <span class="inline-block bg-yellow-300 text-yellow-900 px-3 py-1 rounded-full text-sm">
        {{ 'XUsersOnLine'|get_plugin_lang('BBBPlugin')|format(users_online) }}
      </span>
        {% if max_users_limit > 0 %}
        <p class="mt-2 text-sm text-red-600">
            {{ 'MaxXUsersWarning'|get_plugin_lang('BBBPlugin')|format(max_users_limit) }}
        </p>
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
        <h2 class="text-xl font-bold border-b pb-2 mb-4">
            {{ 'RecordList'|get_plugin_lang('BBBPlugin') }}
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full border text-sm bg-white shadow rounded-lg">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">{{ 'CreatedAt'|get_plugin_lang('BBBPlugin') }}</th>
                    <th class="px-4 py-2">{{ 'Status'|get_lang }}</th>
                    <th class="px-4 py-2">{{ 'Records'|get_plugin_lang('BBBPlugin') }}</th>
                    {% if allow_to_edit %}
                    <th class="px-4 py-2">{{ 'Actions'|get_lang }}</th>
                    {% endif %}
                </tr>
                </thead>
                <tbody>
                {% for meeting in meetings %}
                <tr class="border-t">
                    <td class="px-4 py-2 {% if meeting.visibility == 0 %}text-gray-400{% endif %}">
                        {{ meeting.created_at }}
                    </td>
                    <td class="px-4 py-2">
                        {% if meeting.status == 1 %}
                        <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                      {{ 'MeetingOpened'|get_plugin_lang('BBBPlugin') }}
                    </span>
                        {% else %}
                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                      {{ 'MeetingClosed'|get_plugin_lang('BBBPlugin') }}
                    </span>
                        {% endif %}
                    </td>
                    <td class="px-4 py-2">
                        {% if meeting.show_links.record  %}
                            {% for link in meeting.show_links %}
                                {% if link is not iterable  %}
                                    {{ link }}
                                {% endif %}
                            {% endfor %}
                        {% else %}
                        <span class="text-gray-400">{{ 'NoRecording'|get_plugin_lang('BBBPlugin') }}</span>
                        {% endif %}
                    </td>
                    {% if allow_to_edit %}
                    <td class="px-4 py-2 space-x-2">
                        {% if meeting.status == 1 %}
                        <a href="{{ meeting.end_url }}" class="text-red-600 text-sm hover:underline">
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
    <div class="text-center text-red-50 font-semibold mt-8">
        {{ 'ServerIsNotRunning'|get_plugin_lang('BBBPlugin') }}
    </div>
    {% endif %}
</div>

{% endautoescape %}
