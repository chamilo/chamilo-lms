<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>{{ 'Options' | get_plugin_lang('SepePlugin') }}:</h3></li>
                <li class="sepe-edit-link">
                    <a href="formative-action-edit.php?action_id={{ action_id }}">{{ 'ActionEdit' | get_plugin_lang('SepePlugin') }}</a>
                </li>
                <li class="sepe-delete-link">
                    <input type="hidden" id="action_id"  value="{{ action_id }}"  />
                    <input type="hidden" id="confirmDeleteAction" value="{{ 'confirmDeleteAction' | get_plugin_lang('SepePlugin') }}" />
                    <a href="formative-action-delete.php" id="delete-action">{{ 'DeleteAction' | get_plugin_lang('SepePlugin') }}</a>
                </li>
                <li class="sepe-list-link">
                    <a href="formative-actions-list.php">{{ 'ActionsList' | get_plugin_lang('SepePlugin') }}</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-9">
        {% if message_info != "" %}
            <div class="alert alert-success">
                {{ message_info }}
            </div>
        {% endif %}
        {% if message_error != "" %}
            <div class="alert alert-danger">
                {{ message_error }}
            </div>
        {% endif %}
        <div class="well_border">
            <form class="form-horizontal">
                <fieldset>
                <legend>{{ 'FormativeAction' | get_plugin_lang('SepePlugin') }}:</legend>
                {% if info != false %}
                    <div class="well">
                        <legend><h4>{{ 'ActionIdentifier' | get_plugin_lang('SepePlugin') }}: </h4></legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'ActionOrigin' | get_plugin_lang('SepePlugin') }}:</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.action_origin|e }}</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'ActionCode' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.action_code|e }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'Situation' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <label class="sepe-input-text">
                                {% if info.situation == "10" %}
                                    10-Solicitada Autorización
                                {% endif %}
                                {% if info.situation == "20" %}
                                    20-Programada/Autorizada
                                {% endif %}
                                {% if info.situation == "30" %}
                                    30-Iniciada
                                {% endif %}
                                {% if info.situation == "40" %}
                                    40-Finalizada
                                {% endif %}
                                {% if info.situation == "50" %}
                                    50-Cancelada
                                {% endif %}
                            </label>
                        </div>
                    </div>
                    
                    <div class="well sepe-subfield">
                        <legend><h4>{{ 'MainSpecialtyIdentifier' | get_plugin_lang('SepePlugin') }}</h4></legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'SpecialtyOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.specialty_origin|e }}</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'ProfessionalArea' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.professional_area|e }}</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'SpecialtyCode' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.specialty_code|e }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'Duration' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <label class="sepe-input-text">
                            {% if info.duration > 0 %}
                                {{ info.duration }}
                            {% else %}
                                <i>{{ 'Unspecified' | get_plugin_lang('SepePlugin') }}</i>
                            {% endif %}    
                             </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'StartDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <label class="sepe-input-text">
                            {% if info.start_date == "0000-00-00" %}
                                <i>{{ 'Unspecified' | get_plugin_lang('SepePlugin') }}</i>
                            {% else %}
                                {{ start_date }}
                            {% endif %}                        
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'EndDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <label class="sepe-input-text">
                            {% if info.end_date == "0000-00-00" %}
                                <i>{{ 'Unspecified' | get_plugin_lang('SepePlugin') }}</i>
                            {% else %}
                                {{ end_date }}
                            {% endif %} 
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'FullItineraryIndicator' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <label class="sepe-input-text">{{ info.full_itinerary_indicator }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'FinancingType' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <label class="sepe-input-text">
                            {% if info.financing_type == "PU" %}
                                Pública
                            {% endif %}
                            {% if info.financing_type == "PR" %}
                                Privada
                            {% endif %}
                            {% if info.financing_type == "" %}
                                <i>{{ 'Unspecified' | get_plugin_lang('SepePlugin') }}</i>
                            {% endif %}
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'AttendeesCount' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <label class="sepe-input-text">{{ info.attendees_count }}</label>
                        </div>
                    </div>
                    
                    <div class="well">
                    <legend><h4>{{ 'DescriptionAction' | get_plugin_lang('SepePlugin') }}</h4></legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'NameAction' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.action_name|e }}</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'GlobalInfo' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.global_info|e }}</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'Schedule' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.schedule|e }}</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'Requirements' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.requirements|e }}</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'ContactAction' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text">{{ info.contact_action|e }}</label>
                            </div>
                        </div>
                    </div>
                {% else %}
                    <div class="error-message">{{ 'NoFormativeAction' | get_plugin_lang('SepePlugin') }}</div>
                {% endif %}
                </fieldset>
            </form>
         </div>
         <div class="well_border">
            <form class="form-horizontal">
                <fieldset>
                <legend>{{ 'Specialtys' | get_plugin_lang('SepePlugin') }}:
                    <a href="specialty-action-edit.php?new_specialty=1&action_id={{ action_id }}" class="btn btn-info pull-right">{{ 'CreateSpecialty' | get_plugin_lang('SepePlugin') }}</a>
                </legend>
                <input type="hidden" id="confirmDeleteSpecialty" value="{{ 'confirmDeleteSpecialty' | get_plugin_lang('SepePlugin') }}" />
                {% for specialty in listSpecialty %}
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'Specialty' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <table width="100%" class="sepe-input-text">
                            <tr>
                                <td>{{ specialty.specialty_origin }} {{ specialty.professional_area }} {{ specialty.specialty_code }}</td>
                                <td>
                                    <a href="#" class="btn btn-danger btn-sm pull-right sepe-margin-side delete-specialty" id="specialty{{ specialty.id }}">{{ 'Delete' | get_plugin_lang('SepePlugin') }}</a>
                                    <a href="specialty-action-edit.php?new_specialty=0&specialty_id={{ specialty.id }}&action_id={{ action_id }}" class="btn btn-warning btn-sm pull-right sepe-margin-side">{{ 'Edit' | get_plugin_lang('SepePlugin') }}</a>
                                </td>
                            </tr>
                            </table>
                        </div>
                    </div>
                {% endfor %}                
                </fieldset>
            </form>
         </div>
         
         <div class="well_border">
            <form class="form-horizontal">
                <fieldset>
                <legend>{{ 'Participants' | get_plugin_lang('SepePlugin') }}:
                    <a href="participant-action-edit.php?new_participant=1&action_id={{ action_id }}" class="btn btn-info pull-right">{{ 'CreateParticipant' | get_plugin_lang('SepePlugin') }}</a>
                </legend>
                <input type="hidden" id="confirmDeleteParticipant" value="{{ 'confirmDeleteParticipant' | get_plugin_lang('SepePlugin') }}" />
                {% for participant in listParticipant %}
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'Participant' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <table width="100%" class="sepe-input-text">
                            <tr>
                                <td>{{ participant.firstname }} {{ participant.lastname }} </td>
                                <td>{{ participant.document_number }} {{ participant.documente_letter }} </td>
                                <td>
                                    <a href="#" class="btn btn-danger btn-sm pull-right sepe-margin-side delete-participant" id="participant{{ participant.id }}">{{ 'Delete' | get_plugin_lang('SepePlugin') }}</a>
                                    <a href="participant-action-edit.php?new_participant=0&participant_id={{ participant.id }}&action_id={{ action_id }}" class="btn btn-warning btn-sm pull-right sepe-margin-side">{{ 'Edit' | get_plugin_lang('SepePlugin') }}</a>
                                </td>
                            </tr>
                            </table>
                        </div>
                    </div>
                {% endfor %} 
                </fieldset>
            </form>
         </div>
    </div>
</div>
