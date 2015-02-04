<form id="form_advsub_admin" class="form-search" method="post" action="/plugin/advancedsubscription/src/admin_view.php" name="form_advsub_admin">
    <select id="session-select" name="s">
        {% for sessionItem in sessionItems %}
        <option value="{{ sessionItem.id }}" {{ sessionItem.selected }}>
        {{ sessionItem.name }}
        </option>
        {% endfor %}
    </select>
        <div>
            {{ "Name" | get_lang }}: {{ session.name }}
        </div>
        <div>
            {{ "Target" | get_lang }}: {{ session.target }}
        </div>
        <div>
            {{ "PublicationEndDate" | get_lang }}: {{ session.publication_end_date }}
        </div>
        <div>
            {{ "Mode" | get_lang }}: {{ session.mode }}
        </div>
        <div>
            {{ "RecommendedNumberOfParticipants" | get_lang }} : {{ session.recommended_number_of_participants }}
        </div>
        <div>
            {{ "Vacancies" | get_lang }}: {{ session.vacancies }}
        </div>
    <table class="data_table" id="student_table">
        <tbody>
            <tr class="row_odd">
                <th>Alumno</th>
                <th style="width:180px;">
                    <a href="">
                        Fecha de Inscripción
                    </a>
                </th>
                <th> Validación del Jefe </th>
                <th style="width:70px;">Aceptar</th>
                <th style="width:70px;">Rechazar</th>
            </tr>
            {% set row_class = "row_odd" %}
            {% for student in students %}
                <tr class="{{ row_class }}">
                    <td>{{ student.lastname }} {{ student.firstname }}</td>
                    <td>{{ student.created_at }}</td>
                    <td>{{ student.validation }}</td>
                    <td><a onclick="javascript:if(!confirm('¿Esta seguro de que desea aceptar la inscripción de {{ student.lastname }} {{ student.firstname }}?')) return false;" href="/plugin/advancedsubscription/ajax/advsub.ajax.php?data={{ student.dataApprove }}"><img src="/main/img/icons/22/accept.png" alt="Aceptar" title="Aceptar">
                        </a>
                    </td>
                    <td><a onclick="javascript:if(!confirm('¿Esta seguro de que desea rechazar la inscripción de {{ student.lastname }} {{ student.firstname }}?')) return false;" href="/plugin/advancedsubscription/ajax/advsub.ajax.php?data={{ student.dataDisapprove }}"><img src="/main/img/icons/22/delete.png" alt="Rechazar" title="Rechazar">
                        </a>
                    </td>
                </tr>
            {% if row_class == "row_even" %}
            {% set row_class = "row_odd" %}
            {% else %}
            {% set row_class = "row_even" %}
            {% endif %}
            {% endfor %}
        </tbody>
    </table>
    <input name="f" value="social" type="hidden"><input name="action" type="hidden">
</form>
<script>
    $("#session-select").change(function () {
        $("#form_advsub_admin").submit();
    });
</script>