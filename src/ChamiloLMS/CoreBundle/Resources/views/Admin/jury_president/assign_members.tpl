{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    <a class="btn" href="">Asignar al azar</a>
    <hr>
    <form>

    <table class="table table-bordered">
        <tbody>
        <tr>
            <td>Revisar</td>
            {% for member in members %}
                {% if _u.user_id == member.user.userId %}
                    <td class="hover">
                {% else %}
                    <td>
                {% endif %}

                    {# member.user.userId #}
                    {%  if member.role.role == 'ROLE_JURY_PRESIDENT' %}
                        P{{ loop.index }}
                    {% elseif member.role.role == 'ROLE_JURY_MEMBER' %}
                        M{{ loop.index -1}}
                    {% else %}
                        S{{ loop.index -1}}
                    {% endif %}
                </td>
            {% endfor %}
            <td>Estado</td>
        </tr>
        {% for attempt in attempts %}
            <tr>
                <td>
                    Estudiante #{{ attempt.user.getUserId }}
                </td>
                {% for member in members %}

                    {% if _u.user_id == member.user.userId %}
                        {% set memberHover = 'hover' %}
                    {% else %}
                        {% set memberHover = '' %}
                    {% endif %}

                    {% if relations[attempt.user.getUserId][member.user.userId] == 3 %}
                        {% set checked = 'checked="checked"' %}
                        <td class="{{ memberHover }}">
                            <div class="success">
                    {% else %}
                        {% set checked = '' %}
                        <td class="{{ memberHover }}">
                            <div>
                    {% endif %}

                        <input {{ checked }} id="check_{{ attempt.user.getUserId }}_{{ member.user.userId }}" type="checkbox">
                        </div>
                    </td>
                {% endfor %}
                <td>
                    {% if my_status_for_student[attempt.user.getUserId] %}
                        <a href="#" class="btn btn-success disabled">Evaluado</a>
                    {% else %}
                        <a href="{{ url('jury_member.controller:scoreUserAction', { 'exeId': attempt.getExeId }) }}" class="btn btn-warning">Evaluar</a>
                    {% endif %}
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
    </form>
    <hr>
    <a class="btn" href="">Cierre de proceso</a>
{% endblock %}
