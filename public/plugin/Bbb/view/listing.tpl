{% autoescape false %}
<div class="mx-auto w-full max-w-7xl space-y-6 px-4 pb-8">
    {% if bbb_status %}
    {% if form or show_join_button %}
    <section class="mx-auto max-w-4xl rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        {% if form %}
        {{ form|raw }}
        {% endif %}

        {% if not form and show_join_button %}
        <div class="flex justify-center">
            {% for link in enter_conference_links %}
            {{ link|raw }}
            {% endfor %}
        </div>
        {% endif %}
    </section>
    {% endif %}

    {% if show_join_button and can_see_share_link %}
    <section class="mx-auto max-w-4xl rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-4 text-center">
            <h3 class="mb-1 text-xl font-semibold text-gray-90">
                {{ 'UrlMeetingToShare'|get_plugin_lang('BBBPlugin') }}
            </h3>
            <p class="mb-0 text-body-2 text-gray-50">
                {{ 'Share the conference link with allowed participants.'|get_lang }}
            </p>
        </div>

        <div class="flex flex-col items-stretch justify-center gap-3 md:flex-row md:items-center">
            <input
                    id="share_button"
                    type="text"
                    value="{{ conference_url }}"
                    readonly
                    class="w-full rounded-xl border border-gray-25 bg-white px-4 py-3 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
            >
            <button
                    type="button"
                    onclick="copyTextToClipBoard('share_button');"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary bg-primary px-4 py-3 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
            >
                <i class="fa fa-copy"></i>
                {{ 'Copy text'|get_lang }}
            </button>
        </div>
    </section>
    {% endif %}

    <section class="rounded-2xl border border-gray-25 bg-support-2 p-5 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div class="inline-flex items-center gap-2 rounded-full bg-warning/10 px-3 py-1 text-caption font-semibold text-warning">
                <span>{{ 'XUsersOnLine'|get_plugin_lang('BBBPlugin')|format(users_online) }}</span>
            </div>

            {% if max_users_limit > 0 and users_online >= max_users_limit %}
            <p class="mb-0 text-body-2 font-medium text-danger">
                {% if conference_manager %}
                {{ 'MaxXUsersReachedManager' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}
                {% elseif users_online > 0 %}
                {{ 'MaxXUsersReached' | get_plugin_lang('BBBPlugin') | format(max_users_limit) }}
                {% endif %}
            </p>
            {% elseif max_users_limit > 0 %}
            <p class="mb-0 text-body-2 text-gray-50">
                {{ 'MaxXUsersWarning'|get_plugin_lang('BBBPlugin')|format(max_users_limit) }}
            </p>
            {% endif %}
        </div>
    </section>

    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-2 border-b border-gray-25 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-1 text-2xl font-semibold text-gray-90">
                    {{ 'RecordList'|get_plugin_lang('BBBPlugin') }}
                </h2>
                <p class="mb-0 text-body-2 text-gray-50">
                    {{ 'Review meetings, recordings and available actions.'|get_plugin_lang('BBBPlugin') }}
                </p>
            </div>
            <div class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-caption font-semibold text-support-4">
                {{ meetings|length }} {{ 'Items'|get_lang }}
            </div>
        </div>

        {% if meetings %}
        <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-25">
                    <thead class="bg-support-2">
                    <tr>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'CreatedAt'|get_plugin_lang('BBBPlugin') }}
                        </th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Status'|get_lang }}
                        </th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Records'|get_plugin_lang('BBBPlugin') }}
                        </th>
                        {% if allow_to_edit %}
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Actions'|get_lang }}
                        </th>
                        {% endif %}
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-20">
                    {% for meeting in meetings %}
                    <tr class="transition hover:bg-gray-15">
                        <td class="px-4 py-4 align-top text-body-2 {% if meeting.visibility == 0 %}text-gray-50{% else %}text-gray-90{% endif %}">
                            <div class="font-medium">{{ meeting.created_at }}</div>
                        </td>
                        <td class="px-4 py-4 align-top text-body-2 text-gray-90">
                            {% if meeting.status == 1 %}
                            <span class="inline-flex items-center rounded-full bg-success/10 px-3 py-1 text-caption font-semibold text-success">
                                                    {{ 'MeetingOpened'|get_plugin_lang('BBBPlugin') }}
                                                </span>
                            {% else %}
                            <span class="inline-flex items-center rounded-full bg-info/10 px-3 py-1 text-caption font-semibold text-info">
                                                    {{ 'MeetingClosed'|get_plugin_lang('BBBPlugin') }}
                                                </span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-4 align-top text-body-2 text-gray-90">
                            {% set showLinks = meeting.show_links|default(null) %}

                            {% if showLinks is iterable %}
                            {% if showLinks.record is defined and showLinks.record %}
                            <div class="flex flex-wrap items-center gap-2">
                                {% for key, link in showLinks %}
                                {% if key != 'record' and link is not iterable %}
                                {{ link|raw }}
                                {% endif %}
                                {% endfor %}
                            </div>
                            {% else %}
                            <span class="text-gray-50">
                                                        {{ 'NoRecording'|get_plugin_lang('BBBPlugin') }}
                                                    </span>
                            {% endif %}
                            {% else %}
                            {% if showLinks %}
                            <span class="text-gray-50">{{ showLinks }}</span>
                            {% else %}
                            <span class="text-gray-50">
                                                        {{ 'NoRecording'|get_plugin_lang('BBBPlugin') }}
                                                    </span>
                            {% endif %}
                            {% endif %}
                        </td>
                        {% if allow_to_edit %}
                        <td class="px-4 py-4 align-top text-body-2 text-gray-90">
                            <div class="flex flex-wrap items-center gap-3">
                                {% if meeting.status == 1 %}
                                <a
                                        href="{{ meeting.end_url }}"
                                        class="inline-flex items-center justify-center rounded-xl border border-danger bg-danger px-3 py-2 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
                                >
                                    {{ 'CloseMeeting'|get_plugin_lang('BBBPlugin') }}
                                </a>
                                {% endif %}
                                <div class="flex flex-wrap items-center gap-2">
                                    {{ meeting.action_links|raw }}
                                </div>
                            </div>
                        </td>
                        {% endif %}
                    </tr>
                    {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
        {% else %}
        <div class="rounded-2xl border border-gray-25 bg-support-2 px-6 py-10 text-center">
            <p class="mb-0 text-body-1 font-medium text-gray-90">
                {{ 'No recording'|get_plugin_lang('BBBPlugin') }}
            </p>
            <p class="mt-2 text-body-2 text-gray-50">
                {{ 'No meetings or recordings are available yet.'|get_plugin_lang('BBBPlugin') }}
            </p>
        </div>
        {% endif %}
    </section>
    {% else %}
    <section class="rounded-2xl border border-danger/20 bg-danger/5 px-6 py-10 text-center shadow-sm">
        <div class="mx-auto max-w-2xl">
            <h2 class="mb-3 text-2xl font-semibold text-danger">
                {{ 'ServerIsNotRunning'|get_plugin_lang('BBBPlugin') }}
            </h2>
            <p class="mb-0 text-body-2 text-gray-90">
                {{ 'Check the BigBlueButton server configuration and try again.'|get_plugin_lang('BBBPlugin') }}
            </p>
        </div>
    </section>
    {% endif %}
</div>
{% endautoescape %}
