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
            <h2>{{ theme.title }}</h2>
            {{ theme.content }}
        </td>
        <td>
            {% for plan in plans %}
                <h3>{{ plan.title }}</h3>
                {{ plan.description }}
            {% endfor %}
        </td>
        <td>
            {% for advance in advances %}
                <p>
                    <strong>{{ advance.start_date|local_format_date(2) ~ ' (' ~ advance.duration ~ 'HourShort'|get_lang ~ ') ' }}</strong>
                </p>
                {{ advance.content }}
            {% endfor %}
        </td>
    </tr>
    </tbody>
</table>
