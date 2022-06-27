{% set inc = 0 %}
{% for item in data %}
<div>
    <div style="border: 1px solid #000;padding:1%;background-color: #D9EDF7;color:#31708f;">
        <h1 style="text-decoration: underline;">{{ item.title }}</h1>
        {{ item.content }}
    </div>
    <div style="width: 100%;border: 1px solid #000;background-color: #F5F5F5;">
        <div style="padding:1%;">
            <h1 style="text-decoration: underline;">{{ "ThematicPlan"|get_lang }}</h1>
            {% for plan in item.thematic_plan %}
            <h3>{{ plan.title }}</h3>
            {{ plan.description }}<br>
            {% endfor %}
        </div>
        <div style="border-top: 1px solid #000;padding:1%;">
            <h1 style="text-decoration: underline;">{{ "ThematicAdvance"|get_lang }}</h1>
            {% for advance in item.thematic_advance %}
            <h4>{{ advance.duration }} {{ "MinHours" | get_lang }}</h4>
            {{ advance.start_date | api_convert_and_format_date(2) }}<br>
            {{ advance.content }}
            {% endfor %}
        </div>
    </div>
</div>
{% set inc = inc + 1 %}
{% if (inc < data|length) %}
<pagebreak>
{% endif %}
{% endfor %}