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
        <p>Resultados</p>
        <div id="list" class="trajectory">
            <table class="data_table">
                <tr>
                    <th>
                        Rubro
                    </th>
                    <th>
                        Criterios
                    </th>
                    <th>
                        Puntaje
                    </th>
                    <th>
                        Numero máximo de evidencias por criterio
                    </th>
                    <th>
                        Puntaje máximo por rubro
                    </th>
                </tr>

            {% for subcategory in categories %}
                {% if subcategory.lvl == 0 %}
                {% elseif subcategory.lvl == 1 %}
                    <tr>
                        <td rowspan="{{ category_counter[subcategory.id] | length  + 2 }}">
                         <h3> {{ subcategory.title }} </h3>
                        </td>
                {% else %}
                    <td>
                    <h4>
                        {{ subcategory.title }}
                        {% if category_score[subcategory.id] %}
                            (Total <div class="label label-success">{{ category_score[subcategory.id] }}</div>)
                        {% endif %}
                    </h4>
                    </td>
                    <td>
                    </td>
                    <td>
                    </td>
                    <td>
                    </td>
                    </tr>
                {% endif %}

                {% if subcategory.items.count > 0 %}
                    {% for item in subcategory.items %}
                        <tr>
                        <td>
                        {# Items #}
                        {{ item.title }}
                        {% if 0 %}
                        {{ form_start(form_list[item.id]) }}
                        <div id="items_{{ item.id }}" class="items span8" data-max="{{ item.maxRepeat }}" data-prototype="{{ form_widget(form_list[item.id].userItems.vars.prototype)|e }}" >
                            {% for widget in form_list[item.id].userItems.children %}
                                {{ _self.widget_prototype(widget, 'Remove item') }}
                            {% endfor %}
                            <ul>
                            </ul>
                        </div>
                        {{ form_end(form_list[item.id]) }}
                        {% endif %}

                        </td>
                        <td>
                            {{ item.score }}
                        </td>
                        <td>
                            {{ item.maxRepeat }}
                        </td>
                        <td>
                            {{ category_score[subcategory.parentId]}}
                        </td>
                        </tr>
                    {% endfor %}


                {% endif %}


            {% endfor %}
            </table>
        </div>
    </div>
</div>
{% endblock %}
