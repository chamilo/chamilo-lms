{% if liveMeetings %}
<div class="page-header">
    <h2>{{ 'LiveMeetings'|get_lang }}</h2>
</div>
{% for meeting in liveMeetings %}
<h3>{{ meeting.topic }}</h3>
<p>{{ meeting.agenda }}</p>
<p>
    <a class="btn btn-default" href="{{ meeting.join_url }} ">
        {{ 'Join'|get_lang }}
    </a>
</p>
{% endfor %}
{% else %}
<!-- p>No live meeting currently</p -->
{% endif %}
{% if createInstantMeetingForm %}
{{ createInstantMeetingForm }}
{% endif %}
</div>
<div class ="col-md-12">
    {% if scheduledMeetings %}
    <div class="page-header">
        <h2>{{ 'ScheduledMeetings'|get_lang }}</h2>
    </div>
    <table class="table">
        <tr>
            <!-- th>{{ 'CreatedAt'|get_lang }}</th -->
            <th>{{ 'StartTime'|get_lang }}</th>
            <th>{{ 'Duration'|get_lang }}</th>
            <!-- th>{{ 'Type'|get_lang }}</th -->
            <th>{{ 'TopicAndAgenda'|get_lang }}</th>
            <th></th>
        </tr>
        {% for meeting in scheduledMeetings %}
        <tr>
            <!-- td>{{ meeting.created_at }}</td -->
            <td>{{ meeting.extra_data.formatted_start_time }}</td>
            <td>{{ meeting.extra_data.formatted_duration }}</td>
            <!-- td>{{ meeting.extra_data.type_name }}</td -->
            <td>
                <strong>{{ meeting.topic }}</strong>
                <p class="small">{{ meeting.extra_data.stripped_agenda| nl2br }}</p>
            </td>
            <td>
                <a class="btn" href="{{ meeting.extra_data.meeting_details_url }} ">
                    {{ 'Details'|get_lang }}
                </a>
                <a class="btn" href="{{ meeting.join_url }} ">
                    {{ 'Join'|get_lang }}
                </a>
            </td>
        </tr>
        {% endfor %}
    </table>
    {% else %}
    <!-- p>No scheduled meeting currently</p -->
    {% endif %}
    {% if scheduleMeetingForm %}
    <h3>{{ 'ScheduleMeeting'|get_lang }}</h3>
    {{ scheduleMeetingForm }}
{% endif %}