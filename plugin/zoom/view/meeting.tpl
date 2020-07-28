<h4>
    {{ meeting.typeName }} {{ meeting.id }} ({{ meeting.meetingInfoGet.status }})
</h4>

{% if meeting.meetingInfoGet.status != 'finished' %}
<p>
    <a class="btn btn-primary" href="join_meeting.php?meetingId={{ meeting.id }}">
        {{ 'ViewMeeting'|get_plugin_lang('ZoomPlugin') }}
    </a>
</p>
{% endif %}

{% if isConferenceManager and meeting.status == 'waiting' %}
<p>
    <a href="{{ meeting.meetingInfoGet.start_url }}" target="_blank">
        {{ 'StartMeeting'|get_plugin_lang('ZoomPlugin') }}
    </a>
</p>
{% endif %}

{% if currentUserJoinURL %}
<p>
    <a href="{{ currentUserJoinURL }}" target="_blank">
        {{ 'JoinMeetingAsMyself'|get_plugin_lang('ZoomPlugin') }}
    </a>
</p>
{% endif %}

{% if meeting.meetingInfoGet.settings.approval_type == 2 %}
    <label>
        {{ 'JoinURLToSendToParticipants'|get_plugin_lang('ZoomPlugin') }}
    </label>
    <div class="form-inline">
        <div class="form-group">
            <input class="form-control" type="text"  style="width:300px" readonly value="{{ meeting.meetingInfoGet.join_url }}" />
        </div>
    </div>
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
               {% if registrant.join_url %}
                <a class="btn btn-primary" onclick="copyJoinURL(event, '{{ registrant.join_url }}')">
                    {{ 'CopyJoinAsURL'|get_plugin_lang('ZoomPlugin') }}
                </a>
               {% else %}
                   <a class="btn btn-primary disabled" >
                       {{ 'JoinURLNotAvailable'|get_plugin_lang('ZoomPlugin') }}
                   </a>
               {% endif %}
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
