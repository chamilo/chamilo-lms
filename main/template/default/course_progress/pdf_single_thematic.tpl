<table class="full-width border-thin">
    <thead>
    <tr>
        <th>{{ 'Thematic'|get_lang }}</th>
        <th>{{ 'ThematicPlan'|get_lang }}</th>
        <th>{{ 'ThematicAdvance'|get_lang }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>
            {{ theme.title }}
            {{ theme.content }}
        </td>
        <td>
            {% for plan in plans %}
                {{ plan.title }}
                {{ plan.description }}
            {% endfor %}
        </td>
        <td>
            {% for advance in advances %}
                <p>{{ advance.start_date|api_convert_and_format_date(2) ~ ' (' ~ advance.duration ~ 'HourShort'|get_lang ~ ') ' }}</p>
                {{ advance.content }}
            {% endfor %}
        </td>
    </tr>
    </tbody>
</table>
