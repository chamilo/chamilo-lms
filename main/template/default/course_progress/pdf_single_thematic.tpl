<table class="full-width border-thin">
    <thead>
    <tr>
        <th width="30%" style="display:inline-block; padding: 10px; text-align: center; background-color: #E5E5E5;">{{ 'Thematic'|get_lang }}</th>
        <th width="50%" style="display:inline-block; padding: 10px; text-align: center; background-color: #E5E5E5;">{{ 'ThematicPlan'|get_lang }}</th>
        <th width="20%" style="display:inline-block; padding: 10px; text-align: center; background-color: #E5E5E5;">{{ 'ThematicAdvance'|get_lang }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>
            <h4>{{ theme.title }}</h4>
            <br>
            {{ theme.content }}
        </td>
        <td>
            {% for plan in plans %}
                <br>
                <h4>{{ plan.title }}</h4>
                <br>
                {{ plan.description }}
            {% endfor %}
        </td>
        <td>
            {% for advance in advances %}
                <h4 style="margin-bottom: 10px;">
                    {{ advance.duration }} {{ "MinHours" | get_lang }}
                </h4>
                <p>{{ advance.start_date|api_convert_and_format_date(2) }}</p>
                {{ advance.content }}
                <br>
            {% endfor %}
        </td>
    </tr>
    </tbody>
</table>
