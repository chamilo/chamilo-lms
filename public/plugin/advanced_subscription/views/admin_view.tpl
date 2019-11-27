<form id="form_advanced_subscription_admin" class="form-search" method="post" action="/plugin/advanced_subscription/src/admin_view.php" name="form_advanced_subscription_admin">
    <div class="row">
        <div class="col-md-6">
            <p class="text-title-select">{{ 'SelectASession' | get_plugin_lang('AdvancedSubscriptionPlugin') }}</p>
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
        <div class="col-md-6">
            <p class="separate-badge">
                <span class="badge badge-dis">{{ session.vacancies }}</span>
                {{ "Vacancies" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</p>
            <p class="separate-badge">
                <span class="badge badge-info">{{ session.nbr_users }}</span>
                {{ 'CountOfSubscribedUsers'|get_lang }}
            </p>
            <p class="separate-badge">
                <span class="badge badge-recom">{{ session.recommended_number_of_participants }}</span>
                {{ "RecommendedNumberOfParticipants" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</p>
            <h4>{{ "PublicationEndDate" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</h4> <p>{{ session.publication_end_date }}</p>
            <h4>{{ "Mode" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</h4> <p>{{ session.mode }}</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="student-list-table">
                <table id="student_table" class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ "Postulant" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th>{{ "InscriptionDate" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th>{{ "Area" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th>{{ "Institution" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th>{{ "BossValidation" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                        <th class="advanced-subscription-decision-column">{{ "Decision" | get_plugin_lang('AdvancedSubscriptionPlugin') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {% set row_class = "row_odd" %}
                    {% for student in students %}
                    <tr class="{{ row_class }}">
                        <td class="name">
                            <a href="{{ student.userLink }}" target="_blank">{{ student.complete_name }}<a>
                        </td>
                        <td>{{ student.created_at }}</td>
                        <td>{{ student.area }}</td>
                        <td>{{ student.institution }}</td>
                        {% set cellClass = 'danger'%}
                        {% if student.validation == 'Yes' %}
                            {% set cellClass = 'success'%}
                        {% endif %}
                        <td>
                            {% if student.validation != '' %}
                            <span class="label label-{{ cellClass }}">
                                {{ student.validation | get_plugin_lang('AdvancedSubscriptionPlugin') }}
                            </span>
                            {% endif %}
                        </td>
                        <td>
                            {% if student.status != approveAdmin and student.status != disapproveAdmin %}
                            <a
                                class="btn btn-success btn-advanced-subscription btn-accept"
                                href="{{ student.acceptUrl }}"
                            >
                                {{ 'AcceptInfinitive' | get_plugin_lang('AdvancedSubscriptionPlugin') }}
                            </a>
                            <a
                                class="btn btn-danger btn-advanced-subscription btn-reject"
                                href="{{ student.rejectUrl }}"
                            >
                                {{ 'RejectInfinitive' | get_plugin_lang('AdvancedSubscriptionPlugin') }}
                            </a>
                            {% else %}
                                {% if student.status == approveAdmin%}
                                    <span class="label label-success">{{ 'Accepted'|get_lang }}</span>
                                {% elseif student.status == disapproveAdmin %}
                                    <span class="label label-danger">{{ 'Rejected'|get_lang }}</span>
                                {% endif %}
                            {% endif %}
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
<div class="modal fade" id="modalMail" tabindex="-1" role="dialog" aria-labelledby="modalMailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="modalMailLabel">{{ "AdvancedSubscriptionAdminViewTitle" | get_plugin_lang('AdvancedSubscriptionPlugin')}}</h4>
            </div>
            <div class="modal-body">
                <iframe id="iframeAdvsub" frameBorder="0">
                </iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<link href="{{ _p.web_plugin }}advanced_subscription/views/css/style.css" rel="stylesheet" type="text/css">
<script>
    $(document).ready(function(){
        $("#session-select").change(function () {
            $("#form_advanced_subscription_admin").submit();
        });
        $("a.btn-advanced-subscription").click(function(event){
            event.preventDefault();
            var confirmed = false;
            var studentName = $.trim($(this).closest("tr").find(".name").text());
            if (studentName) {
                ;
            } else {
                studentName = "";
            }
            var msgRe = /%s/;
            if ($(this).hasClass('btn-accept')) {
                var msg = "{{ 'AreYouSureYouWantToAcceptSubscriptionOfX' | get_plugin_lang('AdvancedSubscriptionPlugin') }}";
                var confirmed = confirm(msg.replace(msgRe, studentName));
            } else {
                var msg = "{{ 'AreYouSureYouWantToRejectSubscriptionOfX' | get_plugin_lang('AdvancedSubscriptionPlugin') }}";
                var confirmed = confirm(msg.replace(msgRe, studentName));
            }
            if (confirmed) {
                var tdParent = $(this).closest("td");
                var advancedSubscriptionUrl = $(this).attr("href");
                $("#iframeAdvsub").attr("src", advancedSubscriptionUrl);
                $("#modalMail").modal("show");
                $.ajax({
                    dataType: "json",
                    url: advancedSubscriptionUrl
                }).done(function(result){
                    if (result.error === true) {
                        tdParent.html('');
                    } else {
                        console.log(result);
                    }
                });
            }
        });
    });
</script>
