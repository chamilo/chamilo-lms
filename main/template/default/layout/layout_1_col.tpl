{% extends "default/layout/main.tpl" %}

{#  1 column  #}
{% block body %}

    {#  Plugin top  #}
    {% if plugin_content_top %}
        <div id="plugin_content_top" class="span12">
            {{ plugin_content_top}}
        </div>
    {% endif %}

    <div class="span12">            
        {% include "default/layout/page_body.tpl" %}
        
        {% if content is not null %}
            <section id="main_content">
            {{ content}}           
            </section>
        {% endif %}        
        &nbsp;
    </div>
    
    {#  Plugin bottom  #}    
    {% if plugin_content_bottom %}
        <div id="plugin_content_bottom" class="span12">
            {{ plugin_content_bottom }}
        </div>
    {% endif %}
{% endblock %}