{{ form }}

{% if list %}
<div class ="row">
    <div class ="col-md-12">
        <div class="page-header">
            <h2>{{ 'List'| get_lang }}</h2>
        </div>
        <table class="table">
            <tr>

                <th>{{ 'Justification'| get_plugin_lang('Justification') }}</th>
                <th>{{ 'File'| get_lang }}</th>
                <th>{{ 'Date'| get_lang('Date') }}</th>
                <th>{{ 'Actions'| get_lang }}</th>
            </tr>

            {% for item in list %}
                <tr>

                    <td >{{ item.justification.name }} </td>
                    <td >{{ item.file_path }} </td>
                    <td >{{ item.date_validity }} </td>
                    <td>
                        <a href="{{_p.web_plugin }}justification/justification_by_user.php?a=delete&user_id={{ user_id }}&id={{ item.id }}" class="btn btn-danger">
                            {{'Delete' | get_lang}}
                        </a>
                    </td>
                </tr>
            {% endfor %}
        </table>
    </div>
</div>
{% endif %}
