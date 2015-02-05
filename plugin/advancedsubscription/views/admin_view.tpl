<style type="text/css">
    .text-title-select{
        display: inline-block;
    }
    #session-select{
        display: inline-block;
    }
    .title-name-session{
        display: block;
        padding-top: 10px;
        padding-bottom: 10px;
        font-weight: normal;
        margin-top: 5px;
        margin-bottom: 5px;
    }
    .badge-dis{
        background-color: #008080;
        font-size: 20px;
    }
    .badge-recom{
        background-color:#88aa00 ;
        font-size: 20px;
    }
    .separate-badge{
        margin-bottom: 20px;
        margin-top: 20px;
    }
    .date, .mode{
        display: inline-block;
    }
    .img-circle{
        border-radius: 500px;
        -moz-border-radius: 500px;
        -webkit-border-radius: 500px;
    }
    #student_table.table td{
        vertical-align: middle;
        text-align: center;
    }
    #student_table.table td.name{
        color: #084B8A;
        text-align: left;

    }
    #student_table.table th{
        font-size: 14px;
        vertical-align: middle;
        text-align: center;
    }
</style>

<form id="form_advsub_admin" class="form-search" method="post" action="/plugin/advancedsubscription/src/admin_view.php" name="form_advsub_admin">
    <div class="row">
        <div class="span6">
            <p class="text-title-select">Elige una sesión de formación</p>

            <select id="session-select" name="s">
                {% for sessionItem in sessionItems %}
                <option value="{{ sessionItem.id }}" {{ sessionItem.selected }}>
                {{ sessionItem.name }}
                </option>
                {% endfor %}
            </select>

            <h4>{{ "SessionName" | get_lang }}</h4>
            <h3 class="title-name-session">{{ session.name }}</h3>
            <h4>{{ "Target" | get_lang }}</h4>
            <p>{{ session.target }}</p>

        </div>
        <div class="span6">
            <p class="separate-badge">
                <span class="badge badge-dis">{{ session.vacancies }}</span>
                {{ "Vacancies" | get_lang }}</p>
            <p class="separate-badge">
                <span class="badge badge-recom">{{ session.recommended_number_of_participants }}</span>
                {{ "RecommendedNumberOfParticipants" | get_lang }}</p>
            <h4>{{ "PublicationEndDate" | get_lang }}</h4> <p>{{ session.publication_end_date }}</p>
            <h4>{{ "Mode" | get_lang }}</h4> <p>{{ session.mode }}</p>
        </div>
    </div>
    <div class="row">
        <div class="span12">
            <div class="student-list-table">
                <table id="student_table" class="table table-striped">
                    <tbody>
                    <tr>
                        <th style="width: 118px;"><img src="img/icon-avatar.png"/> </th>
                        <th>{{ "Postulant" | get_lang }}</th>
                        <th>{{ "InscriptionDate" | get_lang }}</th>
                        <th>{{ "BossValidation" | get_lang }}</th>
                        <th>{{ "Decision" | get_lang }}</th>
                    </tr>
                    {% set row_class = "row_odd" %}
                    {% for student in students %}
                    <tr class="{{ row_class }}">
                        <td style="width: 118px;"><img src="{{ student.picture.file }}" class="img-circle"> </td>
                        <td class="name">{{ student.complete_name }}</td>
                        <td>{{ student.created_at }}</td>
                        <td>{{ student.validation }}</td>
                        <td>
                            <a
                                class="btn btn-success"
                                onclick="javascript:if(
                                            !confirm(
                                                '¿Esta seguro de que desea aceptar la inscripción de {{ student.complete_name }}?'
                                            )
                                        ) return false;"
                                href="{{ _p.web_plugin }}advancedsubscription/ajax/advsub.ajax.php?data={{ student.dataApprove }}"
                            >
                                Aceptar
                            </a>
                            <a
                                class="btn btn-danger"
                                onclick="javascript:if(
                                            !confirm(
                                                '¿Esta seguro de que desea rechazar la inscripción de {{ student.complete_name }}?'
                                            )
                                        ) return false;"
                                href="{{ _p.web_plugin }}advancedsubscription/ajax/advsub.ajax.php?data={{ student.dataApprove }}"
                            >
                                Rechazar
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
            </div>
        </div>
    </div>

<input name="f" value="social" type="hidden">
</form>
<script>
    $("#session-select").change(function () {
        $("#form_advsub_admin").submit();
    });
</script>