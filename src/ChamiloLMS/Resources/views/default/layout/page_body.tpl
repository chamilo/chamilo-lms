<div id="page">
    {# Actions #}
    {% if actions != '' %}
        <div class="actions">
            {{ actions }}
        </div>
    {% endif %}

    {% if actions_menu != '' %}
        {{ knp_menu_render('actions_menu', { 'currentClass': 'active'}) }}
    {% endif %}

    <span id="loading_block" style="display:none">Loading ...</span>

    {# Page header #}
    {% if header is not empty %}
        <div class="page-header">
            <h1>{{ header }}</h1>
        </div>
    {% endif %}

    {# Check if security exists #}
    {% if app.security.token and is_granted('ROLE_PREVIOUS_ADMIN') %}
        <div class="alert">
            <a href="{{ path('index', {'_switch_user': '_exit'}) }}">{{ 'Exit impersonation' | trans }}</a>
        </div>
    {% endif %}

    {# Show messages #}
    {% if message != '' %}
        <section id="messages">
            {{ message}}
        </section>
    {% endif %}
    {% include app.template_style ~ "/layout/messages.tpl" %}

    {# Welcome to course block  #}
    {% if welcome_to_course_block %}
        <section id="welcome_to_course">
            {% include app.template_style ~ "/layout/welcome_to_course.tpl" %}
        </section>
    {% endif %}
</div>
