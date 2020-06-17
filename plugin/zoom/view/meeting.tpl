<p>{{ meeting.typeName }} {{ meeting.id }} ({{ meeting.statusName }})</p>
<h2>{{ meeting.topic }}</h2>
{% if meeting.agenda %}
<blockquote>{{ meeting.agenda| nl2br }}</blockquote>
{% endif %}


{% if meeting.type == 2 or meeting.type == 8 %}
<dl class="meeting_properties">
    <dt>{{ 'StartTime'|get_lang }}</dt>
    <dd>{{ meeting.formattedStartTime }}</dd>

    <dt>{{ 'Duration'|get_lang }}</dt>
    <dd>{{ meeting.formattedDuration }}</dd>
</dl>
{% endif %}

{% if isConferenceManager and meeting.status == 'waiting' %}
<p>
    <a class="btn" href="{{ meeting.start_url }}">
        {{ 'StartMeeting'|get_lang }}
    </a>
</p>
{% endif %}

{% if currentUserJoinURL %}
<p>
    <a href="{{ currentUserJoinURL }}">
        {{ 'JoinMeeting'|get_lang }}
    </a>
</p>
{% endif %}

{% if meeting.settings.approval_type == 2 %}
<p>
    <label>
        {{ 'JoinURLToShare'|get_lang }}
        <input readonly value="{{ meeting.join_url }}"/>
    </label>
</p>
{% endif %}

{% if instances %}
<h3>{{ 'InstancesAndRecordings'|get_lang }}</h3>
{% for instance in instances %}
<div>
    <h4>{{ instance.start_time }} ({{ instance.recordings.duration }} minutes)</h4>
    <a href="{{ instance.recordings.share_url }}">
        {{ instance.recordings.recording_count }} recordings
        {{ instance.deleteRecordingsForm }}
    </a>
    <table class="table">
        {% for file in instance.recordings.recording_files %}
        <tr>
            <th>{{ file.file_type }}<th>
            <td>
                <a href="{{file.play_url}}" target="_blank">
                    Play {{file.recording_type}}
                </a>
            </td>
            <td class="right">
                <a href="{{file.download_url}}">Download {{ file.file_size }} bytes</a>
            </td>
            <td>
                {{ file.copyToCourseForm }}
            </td>
        </tr>
        {% endfor %}
        <tr>
            <th colspan="4" class="right">
                {{ 'TotalSize'|get_lang }} {{ instance.recordings.total_size }} bytes
            </th>
            <td>
                {{ instance.copyAllRecordingsToCourseForm }}
            </td>
        </tr>
    </table>
    {% if instance.participants %}
    <h4>{{ 'InstanceParticipants'|get_lang }}</h4>
    <ul>
    {% for participant in instance.participants %}
        <li>
            {{ participant.name }}
        </li>
    {% endfor %}
    </ul>
    {% endif %}
</div>
{% endfor %}
{% endif %}

{% if registrants and isConferenceManager %}
<script>
    function copyJoinURL(event, url) {
        event.target.textContent = '{{ 'CopyingJoinURL'|get_lang|escape }}';
        navigator.clipboard.writeText(url).then(function() {
            event.target.textContent = '{{ 'JoinURLCopied'|get_lang|escape }}';
        }, function() {
            event.target.textContent = '{{ 'CouldNotCopyJoinURL'|get_lang|escape }}' + ' ' + url;
        });
    }
</script>
<table class="table">
    <tr>
        <th>{{ 'RegisteredUsers'|get_lang }}</th>
        <th>{{ 'JoinURL'|get_lang }}</th>
    </tr>
    {% for registrant in registrants %}
    <tr>
        <td>
            {{ registrant.fullName }}
        </td>
        <td>
            <a onclick="copyJoinURL(event, '{{ registrant.join_url }}')">
                {{ 'CopyJoinURL'|get_lang }}
            </a>
        </td>
    </tr>
    {% endfor %}
</table>
{% else %}
<p>
    {{ 'JoinURLToSendToParticipants'|get_lang }}
    {{meeting.join_url}}
</p>
{% endif %}

{% if isConferenceManager %}
{{ editMeetingForm }}
{{ deleteMeetingForm }}
{% if enableParticipantRegistration %}
{{ registerParticipantForm }}
{% endif %}
{% endif %}
