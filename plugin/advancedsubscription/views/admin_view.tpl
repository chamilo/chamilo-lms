<form id="form_advsub_admin" class="form-search" method="post" action="/plugin/advancedsubscription/src/admin_view.php" name="form_advsub_admin">
    <select id="session-select" name="s">
        {% for sessionItem in sessionItems %}
        <option value="{{ sessionItem.id }}" {{ sessionItem.selected }}>
        {{ sessionItem.name }}
        </option>
        {% endfor %}
    </select>
        <div>
            Nombre de la sessión: {{ session.name }}
        </div>
        <div>
            Publico objetivo: {{ session.publico_objetivo }}
        </div>
        <div>
            Fin de publicación: {{ session.fin_publicacion }}
        </div>
        <div>
            Modalidad: {{ session.modalidad }}
        </div>
        <div>
            Número de participantes recomendados : {{ session.participantes_recomendados }}
        </div>
        <div>
            Vacantes: {{ session.vacantes }}
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
                    <td><a onclick="javascript:if(!confirm('¿Esta seguro de que desea aceptar la inscripción de {{ student.lastname }} {{ student.firstname }}?')) return false;" href="/plugin/advancedsubscription/ajax/advsub.ajax.php?action=approve&u={{ student.user_id }}&s={{ session.session_id }}"><img src="/main/img/icons/22/accept.png" alt="Aceptar" title="Aceptar">
                        </a>
                    </td>
                    <td><a onclick="javascript:if(!confirm('¿Esta seguro de que desea rechazar la inscripción de {{ student.lastname }} {{ student.firstname }}?')) return false;" href="/plugin/advancedsubscription/ajax/advsub.ajax.php?action=reject&u={{ student.user_id }}&s={{ session.session_id }}"><img src="/main/img/icons/22/delete.png" alt="Rechazar" title="Rechazar">
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