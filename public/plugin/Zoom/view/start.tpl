{% if instant_meeting_form is defined and instant_meeting_form %}
    <div class="mb-6 flex justify-end">
        {{ instant_meeting_form|raw }}
    </div>
{% endif %}

{% if group_form is defined and group_form %}
    <section class="mb-6 rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        {{ group_form|raw }}
    </section>
{% endif %}

{% if meetings is defined and meetings|length > 0 %}
    <section class="mb-8 rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary">
                    {{ 'ZoomVideoConferences'|get_plugin_lang('ZoomPlugin') }}
                </p>
                <h2 class="text-2xl font-bold text-gray-90">
                    {{ 'ScheduledMeetings'|get_lang }}
                </h2>
            </div>
            <span class="inline-flex w-fit rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                {{ meetings|length }} {{ 'Meetings'|get_plugin_lang('ZoomPlugin') }}
            </span>
        </div>

        <div class="space-y-3">
            {% for meeting in meetings %}
                {% set meeting_info = meeting.meetingInfoGet is defined ? meeting.meetingInfoGet : null %}
                <article class="rounded-xl border border-gray-25 bg-gray-15 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-semibold text-gray-90">
                                {{ meeting_info ? meeting_info.topic|default('') : '' }}
                            </h3>

                            {% if meeting_info and meeting_info.agenda|default('') %}
                                <p class="mt-2 whitespace-pre-line text-sm text-gray-70">
                                    {{ meeting_info.agenda|default('') }}
                                </p>
                            {% endif %}

                            <div class="mt-4 flex flex-wrap gap-2 text-sm text-gray-70">
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1">
                                    <span class="mdi mdi-calendar-clock ch-tool-icon mr-1"></span>
                                    {{ meeting.formattedStartTime|default('') }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1">
                                    <span class="mdi mdi-timer-outline ch-tool-icon mr-1"></span>
                                    {{ meeting.formattedDuration|default('') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <a
                                class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary/90"
                                href="join_meeting.php?meetingId={{ meeting.meetingId }}&{{ _p.web_cid_query }}"
                            >
                                <span class="mdi mdi-login-variant ch-tool-icon mr-1 text-white"></span>
                                {{ 'Join'|get_plugin_lang('ZoomPlugin') }}
                            </a>

                            {% if is_manager is defined and is_manager %}
                                <a
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-50 bg-white px-4 py-2 text-sm font-semibold text-gray-90 hover:bg-gray-15"
                                    href="meeting.php?meetingId={{ meeting.meetingId }}&{{ _p.web_cid_query }}"
                                >
                                    <span class="mdi mdi-information ch-tool-icon mr-1"></span>
                                    {{ 'Details'|get_plugin_lang('ZoomPlugin') }}
                                </a>

                                <a
                                    class="inline-flex items-center justify-center rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-danger/90"
                                    href="start.php?action=delete&meetingId={{ meeting.meetingId }}&{{ _p.web_cid_query }}"
                                    onclick="javascript:if(!confirm('{{ 'AreYouSureToDelete' | get_lang }}')) return false;"
                                >
                                    <span class="mdi mdi-delete ch-tool-icon mr-1 text-white"></span>
                                    {{ 'Delete'|get_lang }}
                                </a>
                            {% endif %}
                        </div>
                    </div>
                </article>
            {% endfor %}
        </div>
    </section>
{% endif %}

{% if schedule_meeting_form is defined and schedule_meeting_form %}
    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        {{ schedule_meeting_form|raw }}
    </section>
{% endif %}
