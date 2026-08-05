{% set meeting_info = meeting.meetingInfoGet is defined ? meeting.meetingInfoGet : null %}
{% set meeting_topic = meeting_info and meeting_info.topic is defined ? meeting_info.topic : '' %}

<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-primary">
                {{ 'ZoomVideoConferences'|get_plugin_lang('ZoomPlugin') }}
            </p>
            <h1 class="text-2xl font-bold text-gray-90">
                {{ meeting_topic|default('') ?: ('Activity'|get_plugin_lang('ZoomPlugin')) }}
            </h1>
            <p class="mt-1 text-sm text-gray-50">
                {{ 'Meeting'|get_lang }} #{{ meeting.meetingId }}
            </p>
        </div>

        <a
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-primary shadow-sm hover:bg-gray-15"
            href="meeting.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}"
        >
            <span class="mdi mdi-information ch-tool-icon" aria-hidden="true"></span>
            {{ 'Details'|get_lang }}
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-25">
        <table class="min-w-full divide-y divide-gray-25 text-sm">
            <thead class="bg-gray-15">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-90">{{ 'Type'|get_lang }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-90">{{ 'Action'|get_plugin_lang('ZoomPlugin') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-90">{{ 'Date'|get_lang }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-90">{{ 'Details'|get_lang }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-25 bg-white">
            {% for activity in meeting.activities %}
                <tr>
                    <td class="px-4 py-3 text-gray-90">{{ activity.type }}</td>
                    <td class="px-4 py-3 text-gray-90">{{ activity.title }}</td>
                    <td class="px-4 py-3 text-gray-70">{{ activity.createdAt|api_convert_and_format_date(3) }}</td>
                    <td class="px-4 py-3 text-gray-70">
                        {% if activity.eventDecoded is defined and activity.eventDecoded.registrant is defined and activity.eventDecoded.registrant %}
                            <span class="font-semibold">{{ 'User'|get_lang }}:</span>
                            {{ activity.eventDecoded.registrant.first_name|default('') }}
                            {{ activity.eventDecoded.registrant.last_name|default('') }}
                            {{ activity.eventDecoded.registrant.email|default('') }}
                            {{ activity.eventDecoded.registrant.status|default('') }}
                        {% elseif activity.eventDecoded is defined and activity.eventDecoded.participant is defined and activity.eventDecoded.participant %}
                            <span class="font-semibold">{{ 'User'|get_lang }}:</span>
                            {{ activity.eventDecoded.participant.user_name|default('') }}
                        {% else %}
                            <span class="text-gray-50">-</span>
                        {% endif %}
                    </td>
                </tr>
            {% else %}
                <tr>
                    <td class="px-4 py-6 text-center text-gray-50" colspan="4">
                        {{ 'No data available'|get_lang }}
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
</section>
