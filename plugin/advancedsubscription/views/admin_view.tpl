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
    #modalMail{
        width: 770px;
        margin-top: -180px !important;
        margin-left:  -385px !important;
    }

    #modalMail .modal-body {
        height: 360px;
        overflow: visible;
    }

    #iframeAdvsub {

    }
</style>

<form id="form_advsub_admin" class="form-search" method="post" action="/plugin/advancedsubscription/src/admin_view.php" name="form_advsub_admin">
    <div class="row">
        <div class="span6">
            <p class="text-title-select">Elige una sesión de formación</p>
            <select id="session-select" name="s">
                <option value="0">
                    {{ "SelectASession" | get_plugin_lang('AdvancedSubscriptionPlugin') }}
                </option>
                {% for sessionItem in sessionItems %}
                <option value="{{ sessionItem.id }}" {{ sessionItem.selected }}>
                {{ sessionItem.name }}
                </option>
                {% endfor %}
            </select>

            <h4>{{ "SessionName" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</h4>
            <h3 class="title-name-session">{{ session.name }}</h3>
            <h4>{{ "Target" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</h4>
            <p>{{ session.target }}</p>

        </div>
        <div class="span6">
            <p class="separate-badge">
                <span class="badge badge-dis">{{ session.vacancies }}</span>
                {{ "Vacancies" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</p>
            <p class="separate-badge">
                <span class="badge badge-recom">{{ session.recommended_number_of_participants }}</span>
                {{ "RecommendedNumberOfParticipants" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</p>
            <h4>{{ "PublicationEndDate" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</h4> <p>{{ session.publication_end_date }}</p>
            <h4>{{ "Mode" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</h4> <p>{{ session.mode }}</p>
        </div>
    </div>
    <div class="row">
        <div class="span12">
            <div class="student-list-table">
                <table id="student_table" class="table table-striped">
                    <tbody>
                    <tr>
                        <th style="width: 118px;"><img src="img/icon-avatar.png"/> </th>
                        <th>{{ "Postulant" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th>{{ "InscriptionDate" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th>{{ "BossValidation" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th>{{ "Decision" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
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
                                class="btn btn-success btn-advsub btn-accept"
                                href="{{ student.acceptUrl }}"
                            >
                                Aceptar
                            </a>
                            <a
                                class="btn btn-danger btn-advsub btn-reject"
                                href="{{ student.rejectUrl }}"
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
<div class="modal fade" id="modalMail" tabindex="-1" role="dialog" aria-labelledby="privacidadLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="privacidadLabel">{{ "AdvancedSubscriptionAdminViewTitle" | get_plugin_lang('AdvancedSubscriptionPlugin')}}</h4>
            </div>
            <div class="modal-body">
                <iframe id="iframeAdvsub" style="width: 100%; height: 100%;" frameBorder="0">
                </iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $("#session-select").change(function () {
            $("#form_advsub_admin").submit();
        });
        $("a.btn-advsub").click(function(event){
            event.preventDefault();
            var confirmed = false;
            var studentName = $(this).closest("tr").find(".name").html();
            if (studentName) {
                studentName = "de " + studentName + " ?";
            } else {
                studentName = " ?"
            }
            if ($(this).hasClass('btn-accept')) {
                var confirmed = confirm(
                    "¿Esta seguro de que desea aceptar la inscripción " + studentName
                );
            } else {
                var confirmed = confirm(
                        "¿Esta seguro de que desea aceptar la inscripción " + studentName
                );
            }
            if (confirmed) {
                var thisBlock = $(this).closest("tr");
                var advsubUrl = $(this).attr("href")
                $("#iframeAdvsub").attr("src", advsubUrl)
                $("#modalMail").modal("show");
                $.ajax({
                    dataType: "json",
                    url: advsubUrl
                }).done(function(result){
                    if (result.error == true) {
                        thisBlock.slideUp();
                    } else {
                        console.log(result);
                    }
                });
            }
        });
    });
</script>