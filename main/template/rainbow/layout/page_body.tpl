{% if introduction != '' %}
    {{ introduction }}
{% endif %}

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
{% if category != '' %}
<div class="section-category">
    <div class="page-header">
        <h3>{{ category.name }}</h3>
    </div>
    <div class="description">
        {{ category.description }}
    </div>
</div>
{% endif %}

{% if message != '' %}
    <section id="messages">
        {{ message}}
    </section>
{% endif %}
