{% extends "default/layout/layout_1_col.tpl" %}

{% block content %}
<form action="#" method="post">
    {{ form_widget(form) }}
    <input class="btn" type="submit" name="submit" value="{{ "Send" | get_lang }}" />
</form>
{% endblock %}