<div class="page-header">
    <h2>{{ 'Meeting'|get_lang }}</h2>
</div>
<style>
    dl.meeting_properties dt {
        margin-top: 1em;
        font-size: smaller;
    }
</style>
<dl class="meeting_properties">
    <dt>{{ 'Course'|get_lang }}</dt>
    <dd>
        {% if meeting.course %}
        <a href="{{ meeting.course.course_public_url }}">
            {{ meeting.course.title }}
        </a>
        {% else %}
        -
        {% endif %}
    </dd>
    <dt>{{ 'Session'|get_lang }}</dt>
    <dd>{{ meeting.session ? meeting.session.name : '-' }}</dd>

    <dt>{{ 'Status'|get_lang }}</dt>
    <dd>{{ meeting.status }}</dd>

    <dt>{{ 'Topic'|get_lang }}</dt>
    <dd>{{ meeting.topic }}</dd>

    <dt>{{ 'Agenda'|get_lang }}</dt>
    <dd>{{ meeting.agenda| nl2br }}</dd>

    <dt>{{ 'Type'|get_lang }}</dt>
    <dd>{{meeting.typeName}}</dd>

    <dt>{{ 'StartTime'|get_lang }}</dt>
    <dd>{{ meeting.formattedStartTime }}</dd>

    <dt>{{ 'Duration'|get_lang }}</dt>
    <dd>{{ meeting.formattedDuration }}</dd>

    {% if isConferenceManager %}

    <dt>{{ 'StartURLNotToBeShared'|get_lang }}</dt>
    <dd><a class="btn" href="{{meeting.start_url}}">Start</a></dd>

    <dt>{{ 'JoinURLToSendToParticipants'|get_lang }}</dt>
    <dd>{{meeting.join_url}}</dd>

    <!-- {{ meeting.settings| var_dump }} -->

    {% else %}

    <dt>{{ 'JoinURL'|get_lang }}</dt>
    <dd><a href="{{meeting.join_url}}">{{meeting.join_url}}</a></dd>

    {% endif %}
</dl>
{% if recordings %}
<h3>{{ 'Recordings'|get_lang }}</h3>
<ul>
    {% for recording in recordings %}
    <li>
        <a href="{{recording.share_url}}">{{recording.share_url}}</a>
    </li>
    {% endfor %}
</ul>
{% endif %}

{% if participants %}
<h3>{{ 'Participants'|get_lang }}</h3>
{{ participants| var_dump }}
<ul>
    {% for participant in participants %}
    <li>
        <a href="{{recording.share_url}}">{{recording.share_url}}</a>
    </li>
    {% endfor %}
</ul>
{% endif %}

{% if isConferenceManager %}
{{ editMeetingForm }}
{{ deleteMeetingForm }}
{% endif %}
