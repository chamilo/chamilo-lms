{% if actions != '' %}
    {{ actions }}
{% endif %}
{{ flash_messages }}
{% if header != '' %}
    <div class="section-page">
        <div class="page-header">
            <h3>{{ header }}</h3>
        </div>
    </div>
{% endif %}
{% if message != '' %}
    <section id="messages">
        {{ message}}
    </section>
{% endif %}
