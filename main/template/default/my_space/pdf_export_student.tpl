<div style="position: absolute; top: 30px; right: 50px;">
    <img src="{{ _p.web_css_theme }}images/header-logo.png" width="200">
</div>

{% if title %}
<h1 style="color:#000000; background-color:transparent;font-size: 22px; text-align: center; font-weight: bold; padding: 5px 10px;">
    {{ title }}
</h1>
{% endif %}

{% if session_title %}
    <h1 style="color:#ffffff; background-color:#084B8A;font-size: 22px; text-align: center; font-weight: bold; padding: 5px 10px;">
        {{ session_title }}
    </h1>
{% endif %}

{% if student %}
<div style="font-weight: bold; padding-bottom: 20px;">
    {{ student }}
</div>
{% endif %}

{% if table_progress %}
<div style="background: transparent;">
    {{ table_progress }}
</div>
{% endif %}

{% if subtitle %}
<div style="padding-bottom: 20px; margin-top: 20px;">
    {{ subtitle }}
</div>
{% endif %}

{% if table_course %}
<div style="background: #f1f6ff;">
    {{ table_course }}
</div>
{% endif %}
