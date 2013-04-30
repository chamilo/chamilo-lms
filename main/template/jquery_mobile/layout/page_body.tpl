{#  Actions  #}
{% if actions != '' %}
    <div class="actions">
        {{ actions }}
    </div>
{% endif %}

{% if actions_menu != '' %}
    {{ knp_menu_render('actions_menu', { 'currentClass': 'active'}) }}
{% endif %}

{#  Page header #}
{% if header != '' %}
    <div class="page-header">
        <h1>{{ header }}</h1>
    </div>
{% endif %}

{#  Show messages #}
{% if message != '' %}
    <section id="messages">
        {{ message}}
    </section>
{% endif %}

{#  Welcome to course block  #}
{% if welcome_to_course_block %}
    <section id="welcome_to_course">
        {% include "jquery_mobile/layout/welcome_to_course.tpl" %}
    </section>
{% endif %}
