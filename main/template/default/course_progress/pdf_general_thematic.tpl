<div style="border: 1px solid #000;">
    <div style="float: left; width: 17%;padding:2%;font-weight: bold;">
        {{ "Thematic"|get_lang }}
    </div>
    <div style="float: left; width: 27%;padding:2%;font-weight: bold;">
        {{ "ThematicPlan"|get_lang }}
    </div>
    <div style="float: left; width: 27%;padding:2%;font-weight: bold;">
        {{ "ThematicAdvance"|get_lang }}
    </div>
</div>
<hr />
{% for item in data %}
    <div style="border: 1px solid #000;">
        <div style="float: left; width: 17%;padding:2%;">
            <h4 style="margin-bottom: 10px;">{{ item.title }}</h4>
            <br>
            {{ item.content }}
        </div>
        <div style="float: left; width: 27%;padding:2%;">
            {% for plan in item.thematic_plan %}
            <br>
            <h4 style="margin-bottom: 10px;">{{ plan.title }}</h4>
            <br>
            {{ plan.description }}
            {% endfor %}
        </div>
        <div style="float: left; width: 27%;padding:2%;">
            {% for advance in item.thematic_advance %}
            <br>
            <h4 style="margin-bottom: 10px;">
                {{ advance.duration }} {{ "MinHours" | get_lang }}
            </h4>
            {{ advance.start_date | api_convert_and_format_date(2) }}
            <br>
            {{ advance.content }}
            {% endfor %}
        </div>
    </div>
{% endfor %}