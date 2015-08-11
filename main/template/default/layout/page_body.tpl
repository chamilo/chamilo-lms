{#  Actions  #}
{% if actions != '' %}
    <div class="actions">
        {{ actions }}
    </div>
{% endif %}

{{ flash_messages }}
<span id="js_alerts"></span>

{#  Page header #}
{% if header != '' %}
    <div class="section-page">
        <div class="page-header">
            <h3>{{ header }}</h3>
        </div>
    </div>
{% endif %}

{#  Show messages #}
{% if message != '' %}
    <section id="messages">
        {{ message}}
    </section>
{% endif %}
