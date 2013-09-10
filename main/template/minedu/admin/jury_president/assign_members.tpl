{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <script>
        $(document).ready(function() {
            $('.inputAction').on('click', function() {
                $isChecked = $(this).is(":checked");
                var idParts = $(this).attr('id').split('_');

                var userId =  idParts[1];
                var juryMemberId = idParts[2];

                if ($isChecked) {
                    url = '{{ _p.public_web }}admin/jury_president/assign-user/'+ userId +'/'+juryMemberId;
                } else {
                    url = '{{ _p.public_web }}admin/jury_president/remove-user/'+ userId +'/'+juryMemberId;
                }
                $.ajax(url);
            });
        });
    </script>

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
            <td>Mi estado</td>
            <td>Estado global (Min {{ considered_evaluated }} evaluados)</td>
        </tr>
        {% for attempt in attempts %}
            <tr>
                <td>
                    Estudiante #{{ attempt.user.getUserId }} ({{ attempt.exeId }})
                </td>
                {% for member in members %}

                    {% if _u.user_id == member.user.userId %}
                        {% set memberHover = 'hover' %}
                    {% else %}
                        {% set memberHover = '' %}
                    {% endif %}

                    {% if relations[attempt.user.getUserId][member.user.userId] %}
                        {% set checkedSuccess = 'class="success"' %}
                        {% set disabled = 'disabled' %}
                    {% else %}
                        {% set checkedSuccess = '' %}
                        {% set disabled = '' %}
                    {% endif %}

                    <td class="{{ memberHover }}">
                        {% set studentList = students_by_member[member.id]  %}
                        {% if attempt.user.getUserId in studentList %}
                            {% set checked = 'checked="checked"' %}
                        {% else %}
                            {% set checked = '' %}
                        {% endif %}
                        <div {{ checkedSuccess }}>
                            <input {{ disabled }} class="inputAction" {{ checked }} id="check_{{ attempt.user.getUserId }}_{{ member.id }}" type="checkbox">
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
                <td>
                    {% if global_student_status[attempt.user.getUserId] %}
                        <a href="#" class="btn btn-success disabled">Completo</a>
                    {% else %}
                        <a href="#" class="btn btn-danger disabled">Incompleto</a>
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
