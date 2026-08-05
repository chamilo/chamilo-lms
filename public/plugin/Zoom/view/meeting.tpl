{% set meeting_info = meeting.meetingInfoGet is defined ? meeting.meetingInfoGet : null %}
{% set meeting_status = meeting_info and meeting_info.status is defined ? meeting_info.status : '' %}
{% set meeting_topic = meeting_info and meeting_info.topic is defined ? meeting_info.topic : '' %}
{% set meeting_agenda = meeting_info and meeting_info.agenda is defined ? meeting_info.agenda : '' %}
{% set meeting_type = meeting_info and meeting_info.type is defined ? meeting_info.type : null %}

<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-primary">
                {{ 'ZoomVideoConferences'|get_plugin_lang('ZoomPlugin') }}
            </p>
            <h1 class="text-2xl font-bold text-gray-90">
                {{ meeting_topic|default('') ?: ('Meeting'|get_lang) }}
            </h1>
            <div class="mt-3 flex flex-wrap gap-2 text-sm text-gray-70">
                {% if meeting.meetingId is defined and meeting.meetingId %}
                    <span class="inline-flex rounded-full bg-gray-15 px-3 py-1">
                        #{{ meeting.meetingId }}
                    </span>
                {% endif %}
                {% if meeting_status %}
                    <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 font-semibold text-blue-700">
                        {{ meeting_status }}
                    </span>
                {% endif %}
                {% if meeting.formattedStartTime is defined and meeting.formattedStartTime %}
                    <span class="inline-flex rounded-full bg-gray-15 px-3 py-1">
                        <span class="mdi mdi-calendar-clock ch-tool-icon mr-1"></span>
                        {{ meeting.formattedStartTime }}
                    </span>
                {% endif %}
                {% if meeting.formattedDuration is defined and meeting.formattedDuration %}
                    <span class="inline-flex rounded-full bg-gray-15 px-3 py-1">
                        <span class="mdi mdi-timer-outline ch-tool-icon mr-1"></span>
                        {{ meeting.formattedDuration }}
                    </span>
                {% endif %}
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            {% if 'finished' != meeting_status %}
                <a
                    class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary/90"
                    href="join_meeting.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}"
                >
                    <span class="mdi mdi-login-variant ch-tool-icon mr-1 text-white"></span>
                    {{ 'ViewMeeting'|get_plugin_lang('ZoomPlugin') }}
                </a>
            {% endif %}

            {% if isConferenceManager is defined and isConferenceManager %}
                {% if 'waiting' == meeting_status and meeting_info and meeting_info.start_url is defined and meeting_info.start_url %}
                    <a
                        class="inline-flex items-center justify-center rounded-lg bg-success px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-success/90"
                        href="{{ meeting_info.start_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="mdi mdi-video ch-tool-icon mr-1 text-white"></span>
                        {{ 'StartMeeting'|get_plugin_lang('ZoomPlugin') }}
                    </a>
                {% endif %}

                <a
                    class="inline-flex items-center justify-center rounded-lg border border-gray-50 bg-white px-4 py-2 text-sm font-semibold text-gray-90 hover:bg-gray-15"
                    href="activity.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}"
                >
                    <span class="mdi mdi-chart-line ch-tool-icon mr-1"></span>
                    {{ 'Activity'|get_plugin_lang('ZoomPlugin') }}
                </a>
            {% endif %}
        </div>
    </div>

    {% if meeting_agenda %}
        <div class="mb-6 rounded-xl bg-gray-15 p-4 text-sm text-gray-90">
            {{ meeting_agenda|nl2br }}
        </div>
    {% endif %}

    {% if meeting_type == 2 or meeting_type == 8 %}
        <dl class="mb-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-25 bg-gray-15 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'StartTime'|get_lang }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-90">{{ meeting.formattedStartTime|default('') }}</dd>
            </div>
            <div class="rounded-xl border border-gray-25 bg-gray-15 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'Duration'|get_lang }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-90">{{ meeting.formattedDuration|default('') }}</dd>
            </div>
        </dl>
    {% endif %}

    {% if isConferenceManager is defined and isConferenceManager %}
        <div class="mb-6 rounded-xl border border-gray-25 bg-gray-15 p-4">
            <p class="mb-3 text-sm font-semibold text-gray-90">
                {{ 'JoinURLToSendToParticipants'| get_plugin_lang('ZoomPlugin') }}
            </p>
            <div class="flex flex-col gap-2 md:flex-row">
                <input
                    id="share_button_flash"
                    type="text"
                    class="w-full rounded-lg border border-gray-25 bg-white px-3 py-2 text-sm text-gray-90"
                    readonly
                    value="{{ url('index') }}plugin/Zoom/join_meeting.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}"
                >
                <button
                    onclick="copyTextToClipBoard('share_button_flash');"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-50 bg-white px-4 py-2 text-sm font-semibold text-gray-90 hover:bg-gray-15"
                    type="button"
                >
                    <span class="mdi mdi-content-copy ch-tool-icon mr-1"></span>
                    {{ 'Copy text' | get_lang }}
                </button>
            </div>
        </div>

        <div class="space-y-4">
            {{ editMeetingForm|default('')|raw }}
            {{ deleteMeetingForm|default('')|raw }}
            {{ registerParticipantForm|default('')|raw }}
            {{ fileForm|default('')|raw }}
        </div>

        {% if registrants is defined and registrants|length > 0 %}
            <script>
                function copyJoinURL(event, url) {
                    event.target.textContent = '{{ 'CopyingJoinURL'|get_plugin_lang('ZoomPlugin')|escape }}';
                    navigator.clipboard.writeText(url).then(
                        function() {
                            event.target.textContent = '{{ 'JoinURLCopied'|get_plugin_lang('ZoomPlugin')|escape }}';
                        }, function() {
                            event.target.textContent = '{{ 'CouldNotCopyJoinURL'|get_plugin_lang('ZoomPlugin')|escape }}' + ' ' + url;
                        }
                    );
                }
            </script>

            <section class="mt-6">
                <h2 class="mb-3 text-xl font-bold text-gray-90">{{ 'Users' | get_lang }}</h2>
                <div class="overflow-hidden rounded-xl border border-gray-25">
                    {% for registrant in registrants %}
                        <div class="flex items-center justify-between border-b border-gray-25 px-4 py-3 last:border-b-0">
                            <span class="text-sm font-semibold text-gray-90">{{ registrant.fullName }}</span>
                        </div>
                    {% endfor %}
                </div>
            </section>
        {% endif %}
    {% endif %}
</section>
