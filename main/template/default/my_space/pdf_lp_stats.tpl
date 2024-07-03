<div style="position: absolute; top: 40px; right: 50px;">
    {{ logo }}
</div>

{% if title %}
<h1 style="color:#084B8A; text-transform: uppercase; background-color:transparent;font-size: 24px; text-align: center; font-weight: bold; padding: 5px 10px; margin-bottom: 10px;">
    {{ title }}
</h1>
{% endif %}

{% if session_title %}
<h1 style="color:#084B8A; font-size: 22px; text-align: center; font-weight: bold; padding: 5px 10px; margin-bottom: 10px;">
    {{ session_title }}
</h1>
{% endif %}

{% if subtitle %}
<div style="padding-bottom: 20px; margin-top: 20px; font-weight: bold;">
    {{ subtitle }}
</div>
{% endif %}

{% if table_course %}
<div style="background: #f1f6ff;">
    {{ table_course }}
</div>
{% endif %}
