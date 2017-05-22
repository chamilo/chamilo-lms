<script type='text/javascript' src="../js/sepe.js"></script>
<script type='text/javascript'>
    $(document).ready(function () {
        $("input[type='submit']").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            if ( $("#slt_user_exists").val() == "1" ) {
                if ($("select[name='existingTutor']").val()=="") {
                    alert("{{ 'SelectUserExistsMessage' | get_plugin_lang('SepePlugin') }}")
                } else {
                    $("form").submit();        
                }
            } else {
                var document_type = $("select[name='document_type']").val();
                var document_number = $("input[name='document_number']").val();
                var document_letter = $("input[name='document_letter']").val();
                var vplatform_user_id = $("select[name='platform_user_id']").val();
                if ($.trim(document_type)=='' || $.trim(document_number)=='' || $.trim(document_letter)=='') {
                    alert("{{ 'RequiredTutorField' | get_plugin_lang('SepePlugin') }}");
                } else {
                    if ($("input[name='new_tutor']" ).val()=="0") {
                        $.post("function.php", {tab:"checkTutorEdit", type:document_type, number:document_number, letter:document_letter, platform_user_id:vplatform_user_id},
                        function (data) {
                            if (data.status == "false") {
                                if (confirm(data.content)) {
                                    $("form").submit();
                                }
                            } else {
                                $("form").submit();
                            }
                        }, "json");
                    } else {
                        $("form").submit();    
                    }
                }
            }
        });
    });
</script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="specialty-tutor-edit.php" method="post" name="form_specialty_action">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Actions' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                        {% if new_tutor == "1" %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                            <input type="hidden" name="new_tutor" value="1" />
                        {% else %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                            <input type="hidden" name="specialtyTutorId" value="{{ tutor_id }}" />
                            <input type="hidden" name="new_tutor" value="0" />
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
            
            {% if new_tutor == "1" %}
                <div class="well_border">
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'UseExistingTutor' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <select  id="slt_user_exists" class="form-control" name="slt_user_exists">
                                <option value="1" selected="selected">{{ 'UseExisting' | get_plugin_lang('SepePlugin') }}</option>
                                <option value="0">{{ 'CreateNewTutor' | get_plugin_lang('SepePlugin') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="well_border" id="tutors-list-layer">
                    <fieldset>
                        <legend>{{ 'TutorsList' | get_plugin_lang('SepePlugin') }}</legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'Tutor' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <select  name="existingTutor" class="form-control">
                                    <option value=""></option>
                                    {% for tutor in ExistingTutorsList %}
                                        <option value="{{ tutor.id }}">{{ tutor.data }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="well_border" style="display:none" id="tutor-data-layer">
            {% else %}
                <input type="hidden" name="slt_user_exists" value="0" />
                <div class="well_border" id="tutor-data-layer">
            {% endif %}
              <fieldset>
                <legend>{{ 'TutorTrainer' | get_plugin_lang('SepePlugin') }}</legend>
                <div class="well">
                    <legend><h4>{{ 'TutorIdentifier' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
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
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="document_number" value="{{ info.document_number }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'DocumentLetter' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-1">
                            <input class="form-control" type="text" name="document_letter" value="{{ info.document_letter }}" />
                        </div>
                    </div>
                    
                    <div class="warning-message">
                        {{ 'DocumentFormatMessage' | get_plugin_lang('SepePlugin') }}
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">{{ 'TutorAccreditation' | get_plugin_lang('SepePlugin') }}: </label>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="tutor_accreditation" value="{{ info.tutor_accreditation }}" style="width:100%" />
                        <div class="alert alert-info sepe-message-info sepe-margin-top">{{ 'TutorAccreditationMessage' | get_plugin_lang('SepePlugin') }}</div>
                    </div>
                </div>
                    
                <div class="form-group">
                    <label class="control-label col-sm-3">{{ 'ProfessionalExperience' | get_plugin_lang('SepePlugin') }}: </label>
                    <div class="col-sm-2">
                        <input class="form-control" class="sepe-numeric-field" type="number" name="professional_experience" value="{{ info.professional_experience }}" />
                    </div>
                    <div class="alert alert-info sepe-message-info col-sm-7">{{ 'ProfessionalExperienceMessage' | get_plugin_lang('SepePlugin') }}</div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">{{ 'TeachingCompetence' | get_plugin_lang('SepePlugin') }}: </label>
                    <div class="col-sm-9">
                        <select  name="teaching_competence" class="form-control" >
                            <option value=""></option>
                            {% if info.teaching_competence == "01" %}
                                <option value="01" selected="selected">{{ 'TeachingCompetence01' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="01">{{ 'TeachingCompetence01' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.teaching_competence == "02" %}
                                <option value="02" selected="selected">{{ 'TeachingCompetence02' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="02">{{ 'TeachingCompetence02' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}{% if info.teaching_competence == "03" %}
                                <option value="03" selected="selected">{{ 'TeachingCompetence03' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="03">{{ 'TeachingCompetence03' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}{% if info.teaching_competence == "04" %}
                                <option value="04" selected="selected">{{ 'TeachingCompetence04' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="04">{{ 'TeachingCompetence04' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}{% if info.teaching_competence == "05" %}
                                <option value="05" selected="selected">{{ 'TeachingCompetence05' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="05">{{ 'TeachingCompetence05' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}{% if info.teaching_competence == "06" %}
                                <option value="06" selected="selected">{{ 'TeachingCompetence06' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="06">{{ 'TeachingCompetence06' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">{{ 'ExperienceTeleforming' | get_plugin_lang('SepePlugin') }}: </label>
                    <div class="col-sm-2">
                        <input class="form-control" type="number" name="experience_teleforming" value="{{ info.experience_teleforming }}" />
                    </div>
                    <div class="col-sm-7 alert alert-info sepe-message-info">{{ 'ExperienceTeleformingMessage' | get_plugin_lang('SepePlugin') }}</div>
                </div>
                    
                <div class="form-group">
                    <label class="control-label col-sm-3">{{ 'TrainingTeleforming' | get_plugin_lang('SepePlugin') }}: </label>
                    <div class="col-sm-9">
                        <select  name="training_teleforming" class="form-control">
                            <option value=""></option>
                            {% if info.training_teleforming == "01" %}
                                <option value="01" selected="selected">{{ 'TrainingTeleforming01' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="01">{{ 'TrainingTeleforming01' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.training_teleforming == "02" %}
                                <option value="02" selected="selected">{{ 'TrainingTeleforming02' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="02">{{ 'TrainingTeleforming02' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.training_teleforming == "03" %}
                                <option value="03" selected="selected">{{ 'TrainingTeleforming03' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="03">{{ 'TrainingTeleforming03' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.training_teleforming == "04" %}
                                <option value="04" selected="selected">{{ 'TrainingTeleforming04' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="04">{{ 'TrainingTeleforming04' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                        </select>
                    </div>
                </div>
                
                <div class="well sepe-subfield">
                    <legend class="sepe-subfield">{{ 'PlatformTeacher' | get_plugin_lang('SepePlugin') | upper }}: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'Teacher' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <select  name="platform_user_id" class="form-control">
                                <option value="" selected="selected"></option>
                                {% for teacher in listTeachers %}
                                    {% if info.platform_user_id == teacher.user_id %}
                                        <option value="{{ teacher.id }}" selected="selected">{{ teacher.firstname }} {{ teacher.lastname }}</option>
                                    {% else %}
                                        <option value="{{ teacher.id }}">{{ teacher.firstname }} {{ teacher.lastname }}</option>
                                    {% endif %}
                                {% endfor %}
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
    </form>
</div>
