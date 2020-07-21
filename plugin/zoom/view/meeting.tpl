<p>
    {{ meeting.typeName }} {{ meeting.id }} ({{ meeting.meetingInfoGet.status }})
</p>
{% if meeting.meetingInfoGet.status != 'finished' %}
<p>
    <a href="join_meeting.php?meetingId={{ meeting.id }}">
        {{ 'EnterMeeting'|get_lang }}
    </a>
</p>
{% endif %}

{% if isConferenceManager and meeting.status == 'waiting' %}
<p>
    <a href="{{ meeting.meetingInfoGet.start_url }}" target="_blank">
        {{ 'StartMeeting'|get_lang }}
    </a>
</p>
{% endif %}

{% if currentUserJoinURL %}
<p>
    <a href="{{ currentUserJoinURL }}" target="_blank">
        {{ 'JoinMeetingAsMyself'|get_lang }}
    </a>
</p>
{% endif %}

{% if meeting.meetingInfoGet.settings.approval_type == 2 %}
<p>
    <label>
        {{ 'JoinURLToSendToParticipants'|get_lang }}
        <input readonly value="{{ meeting.meetingInfoGet.join_url }}">
    </label>
</p>
{% endif %}


{% if isConferenceManager %}

{{ editMeetingForm }}
{{ deleteMeetingForm }}
{{ registerParticipantForm }}
{{ fileForm }}
{% if registrants and meeting.meetingInfoGet.settings.approval_type != 2 %}
<script>
    function copyJoinURL(event, url) {
        event.target.textContent = '{{ 'CopyingJoinURL'|get_lang|escape }}';
        navigator.clipboard.writeText(url).then(
            function() {
                event.target.textContent = '{{ 'JoinURLCopied'|get_lang|escape }}';
            }, function() {
                event.target.textContent = '{{ 'CouldNotCopyJoinURL'|get_lang|escape }}' + ' ' + url;
            }
        );
    }
</script>
<ul>
    {% for registrant in registrants %}
    <li>
        <a onclick="copyJoinURL(event, '{{ registrant.join_url }}')">
            {{ 'CopyJoinAsURL'|get_lang }}
        </a>
        {{ registrant.fullName }}
    </li>
    {% endfor %}
</ul>
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
