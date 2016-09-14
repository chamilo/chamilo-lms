{% include template ~ "/layout/main_header.tpl" %}
{% if show_header == true %}
    {% if plugin_content_top is not null %}
        <div id="plugin_content_top" class="col-md-12">
            {{ plugin_content_top }}
        </div>
    {% endif %}
    <div class="container">
        {% include template ~ "/layout/page_body.tpl" %}
        <section id="main_content">
{% endif %}
