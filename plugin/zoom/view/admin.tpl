{{ search_form }}
<table class="table table-hover table-striped">
    <thead>
        <tr>
            <th>{{ 'StartTime'|get_lang }}</th>
            <th>{{ 'Course'|get_lang }}</th>
            <th>{{ 'Session'|get_lang }}</th>
            <th>{{ 'Topic'|get_lang }}</th>
            <th></th>
        </tr>
    </thead>
        <tbody>
        {% for meeting in meetings %}
            <tr>
                <td>{{ meeting.extra_data.formatted_start_time }}</td>
                <td>{{ meeting.extra_data.course ? meeting.extra_data.course.title : '-' }}</td>
                <td>{{ meeting.extra_data.session ? meeting.extra_data.session.name : '-' }}</td>
                <td>{{ meeting.topic }}</td>
                <td>
                    <a class="btn" href="{{ meeting.extra_data.meeting_details_url }} ">
                        {{ 'Details'|get_lang }}
                    </a>
                </td>
            </tr>
        {% endfor %}
    </tbody>
</table>
