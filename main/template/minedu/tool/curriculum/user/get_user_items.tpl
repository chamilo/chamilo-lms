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

<style>
    .no_border {
        border-top : none !important;
    }
    .center_text {
        vertical-align: middle !important;
        text-align: center !important;
    }
</style>


<div class="row">
    <div class="span12">
        <div class="actions">
            <a href="{{ url('curriculum_user.controller:indexAction',
            { 'userId': userResultId, 'course' : course.code, 'id_session' : course_session.id }) }}">
                {{ 'Edit' | trans }}
            </a>
        </div>

        <h2>Trayectoria</h2>
        <p>Resultados</p>
        <table class="data_table">
            <tr>
                <th class="center_text">
                    Rubro
                </th>
                <th class="center_text">
                    Criterios
                </th>
                <th class="center_text">
                    Puntaje
                </th>
                <th class="center_text" style="width:110px">
                    N° máximo de evidencias por criterio
                </th>
                <th class="center_text" style="width:110px">
                    Puntaje máximo por rubro
                </th>
            </tr>
            {% for subcategory in categories %}
                {% if subcategory.lvl == 0 %}
                {% elseif subcategory.lvl == 1 %}
                    <tr>
                        <td class="center_text"  rowspan="{{ category_counter[subcategory.id] | length  + 3 }}">
                            <h3> {{ subcategory.title }} </h3>
                        </td>
                        <td colspan="3">
                        </td>
                        <td class="center_text" rowspan="{{ category_counter[subcategory.id] | length  + 3 }}">
                            <h4>{{ category_score[subcategory.id]}} </h4>
                        </td>
                        </tr>
                {% else %}
                    <tr>
                        <td class="no_border">
                            <h4>
                                {{ subcategory.title }}
                                {% if category_score[subcategory.id] %}
                                    (Sub total <div class="label label-success">{{ category_score[subcategory.id] }}</div>)
                                {% endif %}
                            </h4>
                        </td>
                        <td class="no_border">
                        </td>
                        <td class="no_border">
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
                        </tr>
                    {% endfor %}


                {% endif %}
            {% endfor %}
            </table>

    </div>
</div>
{% endblock %}
