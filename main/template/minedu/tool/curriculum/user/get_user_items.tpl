{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
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
                    {% set rowSpanCounter = item_counter[subcategory.id] | length + category_counter[subcategory.id] + 1 %}
                    <tr>
                        <td class="center_text" rowspan="{{ rowSpanCounter }}">
                            <h3> {{ subcategory.title }}</h3>
                        </td>
                        <td colspan="3">
                        </td>
                        <td class="center_text" rowspan="{{ rowSpanCounter }}">
                            <h4>
                                {# category_score[subcategory.id] #}
                                {{ subcategory.maxScore }}
                            </h4>
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
                                {{ item.title }}
                            </td>
                            <td>
                                {{ item_list[item.id] }}
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
