{#  Actions  #}
{% if actions is not null %}
    <div class="actions">
        {{ actions }}
    </div>
{% endif %}

{#  Page header #}
{% if header is not null %}    
    <div class="page-header">
        <h1>{{ header }}</h1>
    </div>
{% endif %}

{#  Show messages #}
{% if message is not null %}    
    <section id="messages">
        {{ message}}
    </section>
{% endif %}