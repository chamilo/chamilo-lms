
<table style="margin-top: 30px;" class="full-width border-thin">
    <thead>
        <tr>
            <th width="30%" style="display:inline-block; padding: 10px; text-align: center; background-color: #E5E5E5;">
                {{ "Thematic"|get_lang }}
            </th>
            <th width="50%" style="display:inline-block; padding: 10px; text-align: center; background-color: #E5E5E5;">
                {{ "ThematicPlan"|get_lang }}
            </th>
            <th width="20%" style="display:inline-block; padding: 10px; text-align: center; background-color: #E5E5E5;">
                {{ "ThematicAdvance"|get_lang }}
           </th>
        </tr>
    </thead>
    <tbody>
        {% for item in data %}
            <tr>
                <td>
                    <h4 style="margin-bottom: 10px;">{{ item.title }}</h4>
                    <br>
                    {{ item.content }}
                </td>
                <td>
                    {% for plan in item.thematic_plan %}
                        <br>
                        <h4 style="margin-bottom: 10px;">{{ plan.title }}</h4>
                        <br>
                        {{ plan.description }}
                    {% endfor %}
                </td>
                <td>
                    {% for advance in item.thematic_advance %}
                        <br>
                        <h4 style="margin-bottom: 10px;">
                            {{ advance.duration }} {{ "MinHours" | get_lang }}
                        </h4>
                        {{ advance.start_date | api_convert_and_format_date(2) }}
                        <br>
                        {{ advance.content }}
                    {% endfor %}
                </td>
            </tr>
        {% endfor %}
    </tbody>
</table>