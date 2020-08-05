<div class ="row">
    <div class ="col-md-12">
        <div class="page-header">
            <h2>{{ 'List'| get_lang }}</h2>
        </div>
        <table class="table">
            <tr>
                <th>{{ 'Title'| get_lang }}</th>
                <th>{{ 'Type'| get_lang }}</th>
                <th>{{ 'DaysDifference'| get_lang }}</th>
                <th>{{ 'Actions'| get_lang }}</th>
            </tr>

            {% for item in list %}
                <tr>
                    <td >{{ item.title }}</td>
                    <td >{{ item.event_type }}</td>
                    <td >{{ item.day_diff }}</td>
                    <td>
                        <a href="{{_p.web_main }}notification_event/edit.php?id={{ item.id }}" class="btn btn-primary">
                            {{'Edit' | get_lang}}
                        </a>
                        <a href="{{_p.web_main }}notification_event/list.php?a=delete&id={{ item.id }}" class="btn btn-danger">
                            {{'Delete' | get_lang}}
                        </a>
                    </td>
                </tr>
            {% endfor %}
        </table>
    </div>
</div>
