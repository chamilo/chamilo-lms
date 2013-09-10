{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
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
            <td>Mi estado</td>
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

                    {% if relations[attempt.user.getUserId][member.user.userId] %}
                        {% set checkedSuccess = 'class="success"' %}
                    {% else %}
                        {% set checkedSuccess = '' %}
                    {% endif %}

                    {% set studentList = students_by_member[member.id]  %}
                    {% if attempt.user.getUserId in studentList %}
                        {% set checked = 'checked="checked"' %}
                    {% else %}
                        {% set checked = '' %}
                    {% endif %}

                    <td class="{{ memberHover }}">
                        <div {{ checkedSuccess }}>
                        <input disabled {{ checked }} type="checkbox">
                        </div>
                    </td>
                {% endfor %}
                <td>
                    {% if my_student_status[attempt.user.getUserId] %}
                        <a href="#" class="btn btn-success disabled">Evaluado</a>
                    {% else %}
                        <a href="{{ url('jury_member.controller:scoreAttemptAction', { 'exeId': attempt.getExeId, 'juryId' : jury.id }) }}" class="btn btn-warning">
                            Evaluar
                        </a>
                    {% endif %}
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
    </form>
{% endblock %}
