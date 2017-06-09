<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="specialty-action-edit.php" method="post" name="form_specialty_action">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Actions' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                        {% if new_action == "1" %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="new_specialty" value="1" />
                        {% else %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                            <input type="hidden" name="new_specialty" value="0" />
                        {% endif %}
                        <input type="hidden" name="sec_token" value="{{ sec_token }}" />
                        <input class="btn btn-primary sepe-btn-menu-side" type="submit" value="{{ 'SaveChanges' | get_plugin_lang('SepePlugin') }}"  />
                    </li>
                    <li>
                        <input  class="btn btn-warning sepe-btn-menu-side" type="reset" value="{{ 'Reset' | get_plugin_lang('SepePlugin') }}"  />
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
                    <legend>{{ 'SpecialtyFormativeAction' | get_plugin_lang('SepePlugin') }}</legend>
                    <div class="well">
                        <legend><h4>{{ 'SpecialtyIdentifier' | get_plugin_lang('SepePlugin') | upper}}: </h4></legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ 'SpecialtyOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="text" name="specialty_origin" value="{{ info.specialty_origin }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ 'ProfessionalArea' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="text" name="professional_area" value="{{ info.professional_area }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ 'SpecialtyCode' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-3">
                                <input class="form-control" type="text" name="specialty_code" value="{{ info.specialty_code }}" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="well">
                        <legend><h4>{{ 'DeliveryCenter' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ 'CenterOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="text" name="center_origin" value="{{ info.center_origin }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ 'CenterCode' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-3">
                                <input class="form-control" type="text" name="center_code" value="{{ info.center_code }}" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-lg-3 control-label">{{ 'StartDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-lg-4">
                            <select name="day_start" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in 1..31 %}
                                    <option value="{{ i }}" {% if day_start == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="month_start" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in 1..12 %}
                                    <option value="{{ i }}" {% if month_start == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="year_start" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in list_year %}
                                    {% if year_start == i %}
                                        <option value="{{ i }}" selected="selected">{{ i }}</option>
                                    {% else %}
                                        <option value="{{ i }}">{{ i }}</option>
                                    {% endif %}
                                {% endfor %}
                            </select>
                        </div>
                        <div class="alert alert-info sepe-message-info col-lg-5">
                            {{ 'SpecialtyStartDateMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-lg-3 control-label">{{ 'EndDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-lg-4">
                            <select name="day_end" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in 1..31 %}
                                    <option value="{{ i }}" {% if day_end == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="month_end" class="form-control sepe-slt-date">
                                {% for i in 1..12 %}
                                    <option value="{{ i }}" {% if month_end == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="year_end" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in list_year %}
                                    {% if year_end == i %}
                                        <option value="{{ i }}" selected="selected">{{ i }}</option>
                                    {% else %}
                                        <option value="{{ i }}">{{ i }}</option>
                                    {% endif %}
                                {% endfor %}
                            </select>
                        </div>
                        <div class="col-lg-5 sepe-message-info alert alert-info">
                            {{ 'SpecialtyEndDateMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'ModalityImpartition' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <select  name="modality_impartition" class="chzn-select">
                                <option value=""></option>
                                {% if info.modality_impartition == "TF" %}
                                    <option value="TF" selected="selected">Teleformación</option>
                                {% else %}
                                    <option value="TF">Teleformación</option>
                                {% endif %}
                                {% if info.modality_impartition == "PR" %}
                                    <option value="PR" selected="selected">Presencial</option>
                                {% else %}
                                    <option value="PR">Presencial</option>
                                {% endif %}
                                {% if info.modality_impartition == "PE" %}
                                    <option value="PE" selected="selected">Práctica no laboral (formación) en centro de trabajo</option>
                                {% else %}
                                    <option value="PE">Práctica no laboral (formación) en centro de trabajo</option>
                                {% endif %}
                            </select>
                            <em class="alert alert-info sepe-message-info sepe-margin-top">
                                {{ 'ModalityImpartitionMessage' | get_plugin_lang('SepePlugin') }}
                            </em>
                        </div>
                    </div>
                    
                    <div class="well sepe-subfield">
                        <legend><h4>{{ 'DurationData' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ 'ClassroomHours' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="number" name="classroom_hours" value="{{ info.classroom_hours }}" />
                            </div>
                            <div class="col-sm-7 alert alert-info sepe-message-info">{{ 'ClassroomHoursMessage' | get_plugin_lang('SepePlugin') }}</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ 'DistanceHours' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="number" name="distance_hours" value="{{ info.distance_hours }}" />
                            </div>
                            <div class="col-sm-7 alert alert-info sepe-message-info">{{ 'DistanceHoursMessage' | get_plugin_lang('SepePlugin') }}</div>
                        </div>
                    </div>
                    
                    <div class="well">
                        {% if new_action == "1" %}
                            <legend><h4>{{ 'ClassroomSessionCenter' | get_plugin_lang('SepePlugin') | upper}}: </h4></legend>
                            <div class="alert alert-warning">{{ 'ClassroomSessionCenterMessage' | get_plugin_lang('SepePlugin') }}</div>
                        {% else %}
                            <legend>
                                <h4>
                                    {{ 'ClassroomSessionCenter' | get_plugin_lang('SepePlugin') }}: 
                                    <a href="specialty-classroom-edit.php?new_classroom=1&specialty_id={{ info.id }}&action_id={{ action_id }}" class="btn btn-sm btn-info pull-right">{{ 'CreateClassroomCenter' | get_plugin_lang('SepePlugin') }}</a>
                                </h4>
                            </legend>
                            <input type="hidden" id="confirmDeleteClassroom" value="{{ 'confirmDeleteClassroom'|get_plugin_lang('SepePlugin') }}" />
                            {% for classroom in listClassroom %}
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ 'ClassroomCenter' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-9">
                                        <label class="campo_texto">{{ classroom.center_origin }} {{ classroom.center_code }}
                                            <a href="#" class="btn btn-danger btn-sm pull-right sepe-margin-side delete-classroom" id="classroom{{ classroom.id }}">{{ 'Delete' | get_plugin_lang('SepePlugin') }}</a>
                                            <a href="specialty-classroom-edit.php?new_classroom=0&specialty_id={{ info.id }}&classroom_id={{ classroom.id }}&action_id={{ action_id }}" class="btn btn-warning btn-sm pull-right sepe-margin-side">{{ 'Edit' | get_plugin_lang('SepePlugin') }}</a>
                                        </label>
                                    </div>
                                </div>
                            {% endfor %}
                        {% endif %}
                    </div>
                    
                    <div class="well">
                        {% if new_action == "1" %}
                            <legend><h4>{{ 'TrainingTutors' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                            <div class="alert alert-warning">{{ 'TrainingTutorsMessage' | get_plugin_lang('SepePlugin') }}</div>
                        {% else %}
                            <legend>
                                <h4>
                                    {{ 'TrainingTutors' | get_plugin_lang('SepePlugin') }}:
                                    <a href="specialty-tutor-edit.php?new_tutor=1&specialty_id={{ info.id }}&action_id={{ action_id }}" class="btn btn-sm btn-info pull-right">{{ 'CreateTrainingTutor' | get_plugin_lang('SepePlugin') }}</a>
                                </h4>
                            </legend>
                            <input type="hidden" id="confirmDeleteTutor" value="{{ 'confirmDeleteTutor'|get_plugin_lang('SepePlugin') }}" />
                            {% for tutor in listTutors %}
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ 'TrainingTutor' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-9">
                                        <label class="campo_texto">
                                            {{ tutor.firstname }} {{ tutor.lastname }}
                                             ( {{ tutor.document_number }}-{{ tutor.document_letter }} )
                                            <a href="#" class="btn btn-danger btn-sm pull-right sepe-margin-side delete-tutor" id="tutor{{ tutor.id }}">{{ 'Delete' | get_plugin_lang('SepePlugin') }}</a>
                                            <a href="specialty-tutor-edit.php?new_tutor=0&specialty_id={{ info.id }}&tutor_id={{ tutor.id }}&action_id={{ action_id }}" class="btn btn-warning btn-sm pull-right sepe-margin-side">{{ 'Edit' | get_plugin_lang('SepePlugin') }}</a>
                                        </label>
                                    </div>
                                </div>
                            {% endfor %}
                        {% endif %}
                    </div>
                    
                    <div class="well">
                        <legend><h4>{{ 'ContentUse' | get_plugin_lang('SepePlugin') | upper }}</h4></legend>
                        <div class="well">
                            <legend class="sepe-subfield2">{{ 'MorningSchedule' | get_plugin_lang('SepePlugin') | upper }}</legend>
                            <div class="alert alert-info sepe-message-info">{{ 'MorningScheduleMessage' | get_plugin_lang('SepePlugin') }}</div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'ParticipantsNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="mornings_participants_number" value="{{ info.mornings_participants_number }}" />
                                </div>
                            </div>
                    
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'AccessNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="mornings_access_number" value="{{ info.mornings_access_number }}" />
                                </div>
                            </div>
                        
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'TotalDuration' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="morning_total_duration" value="{{ info.morning_total_duration }}"/>
                                </div>
                            </div>
                        </div>
                        <hr />
                        
                        <div class="well">
                            <legend class="sepe-subfield2">{{ 'AfternoonSchedule' | get_plugin_lang('SepePlugin') | upper }}</legend>
                            <div class="alert alert-info sepe-message-info">{{ 'AfternoonScheduleMessage' | get_plugin_lang('SepePlugin') }}</div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'ParticipantsNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="afternoon_participants_number" value="{{ info.afternoon_participants_number }}" />
                                </div>
                            </div>
                        
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'AccessNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="afternoon_access_number" value="{{ info.afternoon_access_number }}" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'TotalDuration' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="afternoon_total_duration" value="{{ info.afternoon_total_duration }}"/>
                                </div>
                            </div>
                        </div>
                        <hr />
                        
                        
                        <div class="well">
                            <legend class="sepe-subfield2">{{ 'NightSchedule' | get_plugin_lang('SepePlugin') | upper }}</legend>
                            <div class="alert alert-info sepe-message-info">{{ 'NightScheduleMessage' | get_plugin_lang('SepePlugin') }}</div> 
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'ParticipantsNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="night_participants_number" value="{{ info.night_participants_number }}" />
                                </div>
                            </div>
                        
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'AccessNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="night_access_number" value="{{ info.night_access_number }}" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'TotalDuration' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="night_total_duration" value="{{ info.night_total_duration }}"/>
                                </div>
                            </div>
                        </div>
                        <hr />
                        
                        <div class="well">
                            <legend class="sepe-subfield2">{{ 'MonitoringAndEvaluation' | get_plugin_lang('SepePlugin') | upper }}</legend>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'ParticipantsNumber' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="attendees_count" value="{{ info.attendees_count }}" />
                                </div>
                            </div>
                        
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'LearningActivityCount' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="learning_activity_count" value="{{ info.learning_activity_count }}" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'AttemptCount' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="attempt_count" value="{{ info.attempt_count }}"/>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ 'EvaluationActivityCount' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-2">
                                    <input class="form-control" type="number" name="evaluation_activity_count" value="{{ info.evaluation_activity_count }}"/>
                                </div>
                            </div>
                        </div>
                        <hr />
                    </div>
                </fieldset>
            </div>
        </div>
    </form>
</div>
