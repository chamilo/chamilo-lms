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
                <td>{{ meeting.formattedStartTime }}</td>
                <td>{{ meeting.course ? meeting.course.title : '-' }}</td>
                <td>{{ meeting.session ? meeting.session.name : '-' }}</td>
                <td>{{ meeting.topic }}</td>
                <td>
                    <a class="btn" href="{{ meeting.detailURL }} ">
                        {{ 'Details'|get_lang }}
                    </a>
                </td>
            </tr>
        {% endfor %}
    </tbody>
</table>
