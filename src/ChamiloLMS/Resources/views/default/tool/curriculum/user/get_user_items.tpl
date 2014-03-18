{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% macro widget_prototype(widget, remove_text) %}
    {% if widget.get('prototype') %}
        {% set form = widget.get('prototype') %}
        {% set name = widget.get('prototype').get('description') %}
    {% else %}
        {% set form = widget %}
        {% set name = widget.get('full_name') %}
    {% endif %}

    <div data-content="{{ name }}">
        {{ form_widget(form) }}
    </div>
{% endmacro %}

{% block content %}

<script>
$(function() {
});
</script>
<div class="row">
    <div class="span10">
        <h2>Trayectoria</h2>
        <p>Explicaciones</p>
        <p>Las respuestas a este formulario son de carácter jurado.</p>
        <div id="list" class="trajectory">
            {% for subcategory in categories %}
                {% if subcategory.lvl == 0 %}
                   {#  <h3> {{ subcategory.title }} </h3>
                    <hr /> #}
                {% else %}
                    <h4>
                        {{ subcategory.title }}
                        {% if category_score[subcategory.id] %}
                            <div class="label label-success">{{ category_score[subcategory.id] }}</div>
                        {% endif %}
                    </h4>
                {% endif %}

                {% for item in subcategory.items %}
                    {# Items #}
                    {{ item.title }} (item) - {{ 'CurriculumMaximumItem' | trans }} {{ item.maxRepeat }}
                    <div class="row">
                    {{ form_start(form_list[item.id]) }}
                    <div id="items_{{ item.id }}" class="items span8" data-max="{{ item.maxRepeat }}" data-prototype="{{ form_widget(form_list[item.id].userItems.vars.prototype)|e }}" >
                        {% for widget in form_list[item.id].userItems.children %}
                            {{ _self.widget_prototype(widget, 'Remove item') }}
                        {% endfor %}
                        <ul>
                        </ul>
                    </div>
                    {{ form_end(form_list[item.id]) }}
                    </div>
                {% endfor %}
            {% endfor %}
        </div>
    </div>
</div>
{% endblock %}
