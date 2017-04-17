<script type='text/javascript' src="../js/sepe.js"></script>
<script type='text/javascript'>
    $(document).ready(function () {
        $("select[name='company_tutor_id']").change(function(){
            if ($(this).val() == "0") {
                $("#new-company-tutor-layer").show();
            } else {
                $("#new-company-tutor-layer").hide();
            }
        });
        
        $("select[name='training_tutor_id']").change(function(){
            if ($(this).val() == "0") {
                $("#new-training-tutor-layer").show();
            } else {
                $("#new-training-tutor-layer").hide();
            }
        });
    });
</script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="participant-action-edit.php" method="post" name="form_participant_action">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Actions' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                        {% if new_participant == "1" %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="new_participant" value="1" />
                        {% else %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="participant_id" value="{{ participant_id }}" />
                            <input type="hidden" name="new_participant" value="0" />
                        {% endif %}
                        <input type="hidden" name="sec_token" value="{{ sec_token }}" />
                        <input class="btn btn-primary sepe-btn-menu-side" type="submit" value="{{ 'SaveChanges' | get_plugin_lang('SepePlugin') }}"  />
                    </li>
                    <li>
                        <input class="btn btn-warning sepe-btn-menu-side" type="reset" value="{{ 'Reset' | get_plugin_lang('SepePlugin') }}"  />
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
                <fieldset>
                    <legend>{{ 'FormativeActionParticipant' | get_plugin_lang('SepePlugin') }}</legend>
                    <div class="well sepe-subfield">
                        <legend><h4>{{ 'UserPlatformList' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'Student' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input type="hidden" id="alertSelectUser" value="{{ 'alertSelectUser'|get_plugin_lang('SepePlugin') }}" />
                                <select name="platform_user_id" id="platform_user_id" class="form-control">
                                    {% if info_user_platform is empty %}
                                        <option value="" selected="selected"></option>
                                    {% else %}
                                        <option value=""></option>
                                        <option value="{{ info_user_platform.user_id }}" selected="selected">{{ info_user_platform.firstname }} {{ info_user_platform.lastname }}</option>
                                    {% endif %}
                             
                                    {% for student in listStudent %}
                                        <option value="{{ student.user_id }}">{{ student.firstname }} {{ student.lastname }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="well sepe-subfield">
                        <legend><h4>{{ 'ParticipantIdentifier' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'DocumentType' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <select  name="document_type" class="form-control">
                                    <option value=""></option>
                                    {% if info.document_type == "D" %}
                                        <option value="D" selected="selected">{{ 'DocumentTypeD' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="D">{{ 'DocumentTypeD' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.document_type == "E" %}
                                        <option value="E" selected="selected">{{ 'DocumentTypeE' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="E">{{ 'DocumentTypeE' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.document_type == "U" %}
                                        <option value="U" selected="selected">{{ 'DocumentTypeU' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="U">{{ 'DocumentTypeU' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.document_type == "G" %}
                                        <option value="G" selected="selected">{{ 'DocumentTypeG' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="G">{{ 'DocumentTypeG' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.document_type == "W" %}
                                        <option value="W" selected="selected">{{ 'DocumentTypeW' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="W">{{ 'DocumentTypeW' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.document_type == "H" %}
                                        <option value="H" selected="selected">{{ 'DocumentTypeH' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="H">{{ 'DocumentTypeH' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'DocumentNumber' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-3">
                                <input class="form-control" type="text" name="document_number" value="{{ info.document_number }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'DocumentLetter' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="text" name="document_letter" value="{{ info.document_letter }}" />
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            {{ 'DocumentFormatMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'CompetenceKey' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="key_competence" value="{{ info.key_competence }}" />
                        </div>
                    </div>
                    
                    <div class="well sepe-subfield">
                        <legend class="sepe-subfield">{{ 'TrainingAgreement' | get_plugin_lang('SepePlugin') | upper }}: </legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'ContractId' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="contract_id" value="{{ info.contract_id }}" />
                                {{ 'ContractIdMessage' | get_plugin_lang('SepePlugin') }}
                                
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'CompanyFiscalNumber' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="company_fiscal_number" value="{{ info.company_fiscal_number }}" />
                            </div>
                        </div>
                        
                        <div class="well">
                        <legend class="sepe-subfield2">{{ 'TutorIdCompany' | get_plugin_lang('SepePlugin') | upper }}: </legend>
                            <div class="form-group">
                                <label class="control-label col-sm-3">{{ 'CompanyTutorsList' | get_plugin_lang('SepePlugin') }}</label>
                                <div class="col-sm-9">
                                    <select name="company_tutor_id" class="form-control">
                                        <option value="" selected="selected">{{ 'NoTutor' | get_plugin_lang('SepePlugin') }}</option>
                                        <option value="0">{{ 'CreateNewTutorCompany' | get_plugin_lang('SepePlugin') }}</option>
                                        {% for tutor in list_tutor_company %}
                                            {% if tutor.id == info.company_tutor_id or ( info|length == 0 and tutor.id == "1" ) %}
                                                <option value="{{ tutor.id }}" selected="selected">{{ tutor.alias }}</option>
                                            {% else %}
                                                <option value="{{ tutor.id }}">{{ tutor.alias }}</option>   
                                            {% endif %}
                                        {% endfor %}
                                    </select>
                                </div>
                            </div>
                            
                            <div id="new-company-tutor-layer" style="display:none">
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'Name' | get_plugin_lang('SepePlugin') }}</label>
                                    <div class="col-sm-9">
                                        <input class="form-control" type="text" name="tutor_company_alias" value="" />
                                    </div>
                                </div>                      
                            
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'DocumentType' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-9">
                               
                                    <select name="tutor_company_document_type" class="form-control">
                                        <option value="" selected="selected"></option>
                                        <option value="D">{{ 'DocumentTypeD' | get_plugin_lang('SepePlugin') }}</option>
                                        <option value="E">{{ 'DocumentTypeE' | get_plugin_lang('SepePlugin') }}</option>
                                        <option value="U">{{ 'DocumentTypeU' | get_plugin_lang('SepePlugin') }}</option>
                                        <option value="G">{{ 'DocumentTypeG' | get_plugin_lang('SepePlugin') }}</option>
                                        <option value="W">{{ 'DocumentTypeW' | get_plugin_lang('SepePlugin') }}</option>
                                        <option value="H">{{ 'DocumentTypeH' | get_plugin_lang('SepePlugin') }}</option>
                                    </select>
                                </div>
                            </div>
        
                            <div class="form-group">
                                <label class="control-label col-sm-3">{{ 'DocumentNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-3">
                                    <input class="form-control" type="text" name="tutor_company_document_number" value="" />
                                </div>
                            </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'DocumentLetter' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-2">
                                        <input class="form-control" type="text" name="tutor_company_document_letter" value="" />
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning mensaje_info">
                                    {{ 'DocumentFormatMessage' | get_plugin_lang('SepePlugin') }}
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="well">
                        <legend class="sepe-subfield2">{{ 'TutorIdTraining' | get_plugin_lang('SepePlugin') | upper }}: </legend>
                            <div class="form-group">
                                <label class="control-label col-sm-3">{{ 'TrainingTutorsList' | get_plugin_lang('SepePlugin') }}</label>
                                <div class="col-sm-9">
                                    <select name="training_tutor_id" class="form-control">
                                        <option value="" selected="selected">{{ 'NoTutor' | get_plugin_lang('SepePlugin') }}</option>
                                        <option value="0">{{ 'CreateNewTutorTraining' | get_plugin_lang('SepePlugin') }}</option>
                                        {% for tutor in list_tutor_training %}
                                            {% if tutor.id == info.training_tutor_id or ( info|length == 0 and tutor.id == "1" ) %}
                                                <option value="{{ tutor.id }}" selected="selected">{{ tutor.alias }}</option>
                                            {% else %}
                                                <option value="{{ tutor.id }}">{{ tutor.alias }}</option>   
                                            {% endif %}
                                        {% endfor %}
                                    </select>
                                </div>
                            </div>
                        
                            <div id="new-training-tutor-layer" style="display:none">
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'Name' | get_plugin_lang('SepePlugin') }}</label>
                                    <div class="col-sm-9">
                                        <input class="form-control" type="text" name="tutor_training_alias" value="" />
                                    </div>
                                </div> 
                            
                               <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'DocumentType' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-9">
                                        <select name="tutor_training_document_type" class="form-control">
                                            <option value="" selected="selected"></option>
                                            <option value="D">{{ 'DocumentTypeD' | get_plugin_lang('SepePlugin') }}</option>
                                            <option value="E">{{ 'DocumentTypeE' | get_plugin_lang('SepePlugin') }}</option>
                                            <option value="U">{{ 'DocumentTypeU' | get_plugin_lang('SepePlugin') }}</option>
                                            <option value="G">{{ 'DocumentTypeG' | get_plugin_lang('SepePlugin') }}</option>
                                            <option value="W">{{ 'DocumentTypeW' | get_plugin_lang('SepePlugin') }}</option>
                                            <option value="H">{{ 'DocumentTypeH' | get_plugin_lang('SepePlugin') }}</option>
                                        </select>
                                    </div>
                                </div>
                           
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'DocumentNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-3">
                                        <input class="form-control" type="text" name="tutor_training_document_number" value="" />
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'DocumentLetter' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-2">
                                        <input class="form-control" type="text" name="tutor_training_document_letter" value="" />
                                    </div>
                                </div>
                            
                                <div class="alert alert-warning">
                                    {{ 'DocumentFormatMessage' | get_plugin_lang('SepePlugin') }}
                                </div>
                            </div>
                        </div>
                    </div>    
                    
                    <div class="well sepe-subfield">
                        {% if new_participant == "1" %}
                            <legend>{{ 'SpecialtiesParcipant' | get_plugin_lang('SepePlugin') | upper }}: </legend>
                            <div class="alert alert-warning">{{ 'SpecialtiesParcipantMessage' | get_plugin_lang('SepePlugin') }}</div>
                        {% else %}
                            <legend>{{ 'SpecialtiesParcipant' | get_plugin_lang('SepePlugin') | upper }}: 
                                <a href="participant-specialty-edit.php?new_specialty=1&participant_id={{ info.id }}&action_id={{ action_id }}" class="btn btn-sm btn-info pull-right">{{ 'CreateSpecialty' | get_plugin_lang('SepePlugin') }}</a>
                            </legend>
                            <input type="hidden" id="confirmDeleteParticipantSpecialty" value="{{ 'confirmDeleteParticipantSpecialty'|get_plugin_lang('SepePlugin') }}" />
                            {% for specialty in listParticipantSpecialty %}
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'Specialty' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-9">
                                        <label class="sepe-input-text">{{ specialty.specialty_origin }} {{ specialty.professional_area }} {{ specialty.specialty_code }}
                                            <a href="#" class="btn btn-danger btn-sm pull-right sepe-margin-side delete-specialty-participant" id="specialty{{ specialty.id }}">{{ 'Delete' | get_plugin_lang('SepePlugin') }}</a>
                                            <a href="participant-specialty-edit.php?new_specialty=0&participant_id={{ info.id }}&specialty_id={{ specialty.id }}&action_id={{ action_id }}" class="btn btn-warning btn-sm pull-right sepe-margin-side">{{ 'Edit' | get_plugin_lang('SepePlugin') }}</a>
                                        </label>
                                    </div>
                                </div>
                            {% endfor %}
                        {% endif %}
                    </div>
                </fieldset>
            </div>
        </div>
    </form>
</div>
