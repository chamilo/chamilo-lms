<h2 class="page-header">
    {{ meeting.topic}}
    <small>{{ meeting.typeName }}</small>
</h2>

<dl class="meeting_properties dl-horizontal">
    {% if meeting.requiresDateAndDuration %}
        <dt>{{ 'StartTime'|get_lang }}</dt>
        <dd>{{ meeting.formattedStartTime }}</dd>

        <dt>{{ 'Duration'|get_lang }}</dt>
        <dd>{{ meeting.formattedDuration }}</dd>
    {% endif %}

    {% if meeting.accountEmail %}
        <dt>{{ 'AccountEmail'|get_lang }}</dt>
        <dd>{{ meeting.accountEmail }}</dd>
    {% endif %}
</dl>

{% if meeting.agenda %}
    <p class="lead">{{ meeting.agenda|nl2br }}</p>
{% endif %}
