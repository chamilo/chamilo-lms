<h4>
    {{ meeting.typeName }} {{ meeting.meetingId }} ({{ meeting.meetingInfoGet.status }})
</h4>

<div class="btn-group" role="group">

{% if meeting.meetingInfoGet.status != 'finished' %}
    <a class="btn btn--primary" href="join_meeting.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}">
        {{ 'ViewMeeting'|get_plugin_lang('ZoomPlugin') }}
    </a>
{% endif %}

{% if isConferenceManager %}
    {% if meeting.status == 'waiting' %}
        <a class="btn btn--primary" href="{{ meeting.meetingInfoGet.start_url }}" target="_blank">
            {{ 'StartMeeting'|get_plugin_lang('ZoomPlugin') }}
        </a>
    {% endif %}

    <a class="btn btn--plain" href="activity.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}">
        {{ 'Activity'|get_plugin_lang('ZoomPlugin') }}
    </a>
{% endif %}
</div>

{% if isConferenceManager %}
    <br />
    <br />
    <div class="panel panel-default conference">
        <div class="panel-body">
            <div class="share">
                {{ 'JoinURLToSendToParticipants'| get_plugin_lang('ZoomPlugin') }}
            </div>
            <div class="form--inline">
                <div class="form-group">
                    <input id="share_button_flash" type="text"
                           style="width:460px"
                           class="form-control" readonly
                           value="{{ _p.web }}plugin/zoom/join_meeting.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}"
                    >
                    <button onclick="copyTextToClipBoard('share_button_flash');" class="btn btn--plain">
                        <span class="fa fa-copy"></span> {{ 'Copy text' | get_lang }}
                    </button>
                </div>
            </div>
        </div>
    </div>
{% endif %}

{% if currentUserJoinURL %}
{#<p>#}
{#    <a href="{{ currentUserJoinURL }}" target="_blank">#}
{#        {{ 'JoinMeeting'|get_plugin_lang('ZoomPlugin') }}#}
{#    </a>#}
{#</p>#}
{% endif %}

{% if isConferenceManager %}
    {{ editMeetingForm }}
    {{ deleteMeetingForm }}
    {{ registerParticipantForm }}
    {{ fileForm }}

    {#    {% if registrants and meeting.meetingInfoGet.settings.approval_type != 2 %}#}
    {% if registrants.count > 0 %}
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
        <h3>{{ 'Users' | get_lang }}</h3>
        <br />
        <table class="table">
            {% for registrant in registrants %}
            <tr>
                <td>
                {{ registrant.fullName }}
                </td>
               <td>
{#               {% if registrant.joinUrl %}#}
{#                <a class="btn btn--primary" onclick="copyJoinURL(event, '{{ registrant.joinUrl }}')">#}
{#                    {{ 'CopyJoinAsURL'|get_plugin_lang('ZoomPlugin') }}#}
{#                </a>#}
{#               {% else %}#}
{#                   <a class="btn btn--primary disabled" >#}
{#                       {{ 'JoinURLNotAvailable'|get_plugin_lang('ZoomPlugin') }}#}
{#                   </a>#}
{#               {% endif %}#}
               </td>
            </tr>
            {% endfor %}
        </table>
    {% endif %}
{% else %}
    <h2>{{ meeting.meetingInfoGet.topic }}</h2>
    {% if meeting.meetingInfoGet.agenda %}
    <blockquote>{{ meeting.meetingInfoGet.agenda| nl2br }}</blockquote>
    {% endif %}

    {% if meeting.meetingInfoGet.type == 2 or meeting.meetingInfoGet.type == 8 %}
    <dl class="meeting_properties">
        <dt>{{ 'StartTime'|get_lang }}</dt>
        <dd>{{ meeting.formattedStartTime }}</dd>

        <dt>{{ 'Duration'|get_lang }}</dt>
        <dd>{{ meeting.formattedDuration }}</dd>
    </dl>
    {% endif %}
{% endif %}
