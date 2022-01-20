{% if meeting.meetingInfoGet %}
    <h2>
        {{ meeting.meetingInfoGet.topic }}
        <small>{{ meeting.typeName }}</small>
    </h2>

    {% if meeting.meetingInfoGet.agenda %}
        <p class="lead">{{ meeting.meetingInfoGet.agenda|nl2br }}</p>
    {% endif %}

    {% if meeting.meetingInfoGet.type == 2 or meeting.meetingInfoGet.type == 8 %}
        <dl class="meeting_properties dl-horizontal">
            <dt>{{ 'StartTime'|get_lang }}</dt>
            <dd>{{ meeting.formattedStartTime }}</dd>

            <dt>{{ 'Duration'|get_lang }}</dt>
            <dd>{{ meeting.formattedDuration }}</dd>
        </dl>
    {% endif %}
{% elseif meeting.webinarSchema %}
    <h2>
        {{ meeting.webinarSchema.topic }}
        <small>{{ meeting.typeName }}</small>
    </h2>

    {% if meeting.webinarSchema.agenda %}
        <p class="lead">{{ meeting.webinarSchema.agenda|nl2br }}</p>
    {% endif %}

    {% if meeting.webinarSchema.type == 5 or meeting.webinarSchema.type == 9 %}
        <dl class="meeting_properties dl-horizontal">
            <dt>{{ 'StartTime'|get_lang }}</dt>
            <dd>{{ meeting.formattedStartTime }}</dd>

            <dt>{{ 'Duration'|get_lang }}</dt>
            <dd>{{ meeting.formattedDuration }}</dd>
        </dl>
    {% endif %}
{% endif %}
