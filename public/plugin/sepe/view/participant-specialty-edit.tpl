<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="participant-specialty-edit.php" method="post" name="form_specialty_action">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Actions' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                        {% if new_specialty == "1" %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="participant_id" value="{{ participant_id }}" />
                            <input type="hidden" name="new_specialty" value="1" />
                        {% else %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                            <input type="hidden" name="participant_id" value="{{ participant_id }}" />
                            <input type="hidden" name="new_specialty" value="0" />
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
                    <legend>{{ 'SpecialtiesParcipant' | get_plugin_lang('SepePlugin') | upper }}</legend>
                    <div class="well sepe-subfield">
                        <legend><h4>{{ 'SpecialtyIdentifier' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'SpecialtyOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="specialty_origin" value="{{ info.specialty_origin }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'ProfessionalArea' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="professional_area" value="{{ info.professional_area }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'SpecialtyCode' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="specialty_code" value="{{ info.specialty_code }}" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">{{ 'RegistrationDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-lg-4">
                            <select name="day_registration" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in 1..31 %}
                                    <option value="{{ i }}" {% if day_registration == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="month_registration" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in 1..12 %}
                                    <option value="{{ i }}" {% if month_registration == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="year_registration" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in list_year %}
                                    {% if year_registration == i %}
                                        <option value="{{ i }}" selected="selected">{{ i }}</option>
                                    {% else %}
                                        <option value="{{ i }}">{{ i }}</option>
                                    {% endif %}
                                {% endfor %}
                            </select>
                        </div>
                        <div class="alert alert-info col-lg-5 sepe-message-info">
                            {{ 'RegistrationDateMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">{{ 'LeavingDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-lg-4">
                            <select name="day_leaving" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in 1..31 %}
                                    <option value="{{ i }}" {% if day_leaving == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="month_leaving" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in 1..12 %}
                                    <option value="{{ i }}" {% if month_leaving == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                {% endfor %}
                            </select>
                            /
                            <select name="year_leaving" class="form-control sepe-slt-date">
                                <option value=""></option>
                                {% for i in list_year %}
                                    {% if year_leaving == i %}
                                        <option value="{{ i }}" selected="selected">{{ i }}</option>
                                    {% else %}
                                        <option value="{{ i }}">{{ i }}</option>
                                    {% endif %}
                                {% endfor %}
                            </select>
                        </div>
                        <div class="alert alert-info col-lg-5">
                            {{ 'LeavingDateMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="well sepe-subfield">
                        {% if new_specialty == "1" %}
                            <legend>{{ 'ClassroomTutorials' | get_plugin_lang('SepePlugin') | upper }}: </legend>
                            <div class="alert alert-warning">
                                {{ 'ClassroomTutorialsMessage' | get_plugin_lang('SepePlugin') }}
                            </div>
                        {% else %}
                            <legend>{{ 'ClassroomTutorials' | get_plugin_lang('SepePlugin') | upper }}: 
                                <a href="specialty-tutorial-edit.php?new_tutorial=1&specialty_id={{ info.id }}&action_id={{ action_id }}" class="btn btn-sm btn-info pull-right">{{ 'CreateClassroomTutorial' | get_plugin_lang('SepePlugin') }}</a>
                            </legend>
                            {% for tutorial in listSpecialtyTutorials %}
                                <div class="form-group">
                                    <label class="control-label col-sm-3">{{ 'ClassroomTutorial' | get_plugin_lang('SepePlugin') }}: </label>
                                    <div class="col-sm-9">
                                        <label class="campo_texto">{{ tutorial.center_origin }} {{ tutorial.center_code }}
                                        <a href="#" class="btn btn-danger btn-sm pull-right sepe-margin-side del_classroom" id="tutorial{{ tutorial.id }}">{{ 'Delete' | get_plugin_lang('SepePlugin') }}</a>
                                        <a href="specialty-tutorial-edit.php?new_tutorial=0&specialty_id={{ info.id }}&tutorial_id={{ tutorial.id }}&action_id={{ action_id }}" class="btn btn-warning btn-sm pull-right sepe-margin-side">{{ 'Edit' | get_plugin_lang('SepePlugin') }}</a>
                                        </label>
                                    </div>
                                </div>
                            {% endfor %}
                        {% endif %}
                    </div>
                    
                    <div class="well sepe-subfield">
                        <legend><h4>{{ 'FinalEvaluation' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="well">
                            <legend class="sepe-subfield2">{{ 'FinalEvaluationClassroom' | get_plugin_lang('SepePlugin') | upper }}</legend>
                            <div class="form-group">
                                <label class="control-label col-sm-3">{{ 'CenterOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="text" name="center_origin" value="{{ info.center_origin }}" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-sm-3">{{ 'CenterCode' | get_plugin_lang('SepePlugin') }}: </label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="text" name="center_code" value="{{ info.center_code }}" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">{{ 'StartDate' | get_plugin_lang('SepePlugin') }}: </label>
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
                                    {% for i in list_year_2 %}
                                        {% if year_start == i %}
                                            <option value="{{ i }}" selected="selected">{{ i }}</option>
                                        {% else %}
                                            <option value="{{ i }}">{{ i }}</option>
                                        {% endif %}
                                    {% endfor %}
                                </select>
                            </div>
                            <div class="alert alert-info col-lg-5 sepe-message-info">
                                {{ 'StartDateMessageEvaluation' | get_plugin_lang('SepePlugin') }}
                            </div>
                        </div>
                    
                        <div class="form-group">
                            <label class="control-label col-lg-3">{{ 'EndDate' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-lg-4">
                                <select name="day_end" class="form-control sepe-slt-date">
                                    <option value=""></option>
                                    {% for i in 1..31 %}
                                        <option value="{{ i }}" {% if day_end == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                    {% endfor %}
                                </select>
                                /
                                <select name="month_end" class="form-control sepe-slt-date">
                                    <option value=""></option>
                                    {% for i in 1..12 %}
                                        <option value="{{ i }}" {% if month_end == i %} selected="selected" {% endif %} >{{ "%02d"|format(i) }}</option>
                                    {% endfor %}
                                </select>
                                /
                                <select name="year_end" class="form-control sepe-slt-date">
                                    <option value=""></option>
                                    {% for i in list_year_2 %}
                                        {% if year_end == i %}
                                            <option value="{{ i }}" selected="selected">{{ i }}</option>
                                        {% else %}
                                            <option value="{{ i }}">{{ i }}</option>
                                        {% endif %}
                                    {% endfor %}
                                </select>
                            </div>
                            <div class="alert alert-info col-lg-5 sepe-message-info">
                                {{ 'EndDateMessageEvaluation' | get_plugin_lang('SepePlugin') }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="well sepe-subfield">
                    <legend><h4>{{ 'Results' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'FinalResult' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <select  name="final_result" class="form-control">
                                    <option value=""></option>
                                    {% if info.final_result == "0" %}
                                        <option value="0" selected="selected">{{ 'Initiated' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="0">{{ 'Initiated' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.final_result == "1" %}
                                        <option value="1" selected="selected">{{ 'LeavePlacement' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="1">{{ 'LeavePlacement' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.final_result == "2" %}
                                        <option value="2" selected="selected">{{ 'AbandonOtherReasons' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="2">{{ 'AbandonOtherReasons' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.final_result == "3" %}
                                        <option value="3" selected="selected">{{ 'EndsPositiveEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="3">{{ 'EndsPositiveEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.final_result == "4" %}
                                        <option value="4" selected="selected">{{ 'EndsNegativeEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="4">{{ 'EndsNegativeEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.final_result == "5" %}
                                        <option value="5" selected="selected">{{ 'EndsNoEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="5">{{ 'EndsNoEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.final_result == "6" %}
                                        <option value="6" selected="selected">{{ 'FreeEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="6">{{ 'FreeEvaluation' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                    {% if info.final_result == "7" %}
                                        <option value="7" selected="selected">{{ 'Exempt' | get_plugin_lang('SepePlugin') }}</option>
                                    {% else %}
                                        <option value="7">{{ 'Exempt' | get_plugin_lang('SepePlugin') }}</option>
                                    {% endif %}
                                </select>
                                <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'FinalResultMessage' | get_plugin_lang('SepePlugin') }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'FinalQualification' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="final_qualification" value="{{ info.final_qualification }}" />
                                <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'FinalQualificationMessage' | get_plugin_lang('SepePlugin') }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'FinalScore' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <input class="form-control" class="form-control" type="text" name="final_score" value="{{ info.final_score }}" />
                                <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'FinalScoreMessage' | get_plugin_lang('SepePlugin') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </form>
</div>
