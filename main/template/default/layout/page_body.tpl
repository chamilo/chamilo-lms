{#  Actions  #}
{% if actions != '' %}
    <div class="actions">
        {{ actions }}
    </div>
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