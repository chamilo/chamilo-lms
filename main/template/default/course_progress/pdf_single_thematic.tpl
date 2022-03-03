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
<div style="border: 1px solid #000;">
    <div style="float: left; width: 17%;padding:2%;">
        <h4 style="margin-bottom: 10px;">{{ theme.title }}</h4>
        <br>
        {{ theme.content }}
    </div>
    <div style="float: left; width: 27%;padding:2%;">
        {% for plan in plans %}
        <br>
        <h4>{{ plan.title }}</h4>
        <br>
        {{ plan.description }}
        {% endfor %}
    </div>
    <div style="float: left; width: 27%;padding:2%;">
        {% for advance in advances %}
        <h4 style="margin-bottom: 10px;">
            {{ advance.duration }} {{ "MinHours" | get_lang }}
        </h4>
        <p>{{ advance.start_date|api_convert_and_format_date(2) }}</p>
        {{ advance.content }}
        <br>
        {% endfor %}
    </div>
</div>
