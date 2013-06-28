{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a class="btn" href="">Asignar al azar</a>
    <table class="table table-bordered">
        <tbody>
        <tr>
            <td>Revisar</td>
            {% for member in members %}
                <td>Postulante {{ member.firstName }}</td>
            {% endfor %}
            <td>Estado</td>
        </tr>
        {% for student in students %}
            <tr>
                <td>
                    {{ student.firstName }}
                </td>
                {% for member in members %}
                <td>
                    11
                </td>
                {% endfor %}
                <td>
                    Evaluado?
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>

    <a class="btn" href="">Cierre de proceso</a>
{% endblock %}
