{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <h2>{{ 'Upgrade' }}</h2>
    <h3>{{ 'ClickToUpgradeToVersion' }} {{ version }}</h3>

    <a class="btn" href="{{ url('upgrade.controller:updateChashAction') }}">Update Chash </a>

    <form action="{{ url('upgrade.controller:upgradeAction', { 'version': version }) }}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>

{% endblock %}
