<div class ="row">
    <div class ="col-md-12">
        <div class="page-header">
            <h2>{{ 'List'| get_lang }}</h2>
        </div>
        <table class="table">
            <tr>
                <th>{{ 'Name'| get_plugin_lang('Justification') }}</th>
                <th>{{ 'validity_duration'| get_lang }}</th>
                <th>{{ 'date_manual_on'| get_plugin_lang('Justification') }}</th>
                <th>{{ 'Actions'| get_lang }}</th>
            </tr>

            {% for item in list %}
                <tr>
                    <td >{{ item.name }} ({{ item.code }})</td>
                    <td >{{ item.validity_duration }}</td>

                    <td >{{ item.date_manual_on }}</td>
                    <td>
                        <a href="{{_p.web_plugin }}justification/list.php?a=delete&id={{ item.id }}" class="btn btn-danger">
                            {{'Delete' | get_lang}}
                        </a>
                    </td>
                </tr>
            {% endfor %}
        </table>
    </div>
</div>
