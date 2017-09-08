<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="formative-action-edit.php" method="post" name="data-center-form">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Actions' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                        {% if new_action == "1" %}
                            <input type="hidden" name="action_id" value="0" />
                            <input type="hidden" name="course_id" value="{{ course_id }}" />
                        {% else %}
                            <input type="hidden" name="action_id" value="{{ info.id }}" />
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
                    <legend>{{ 'FormativeAction' | get_plugin_lang('SepePlugin') }}</legend>
                    <div class="well">
                        <legend><h4>{{ 'ActionIdentifier' | get_plugin_lang('SepePlugin') | upper }}: </h4></legend>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'ActionOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-2">
                                <input class="form-control" type="text" name="action_origin" value="{{ info.action_origin }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'ActionCode' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-2">
                                <input class="form-control" type="text" name="action_code" value="{{ info.action_code }}" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ 'Situation' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-md-9">
                            <select  name="situation" class="form-control">
                            <option value=""></option>
                            {% if info.situation == "10" %}
                                <option value="10" selected="selected">{{ 'Situation10' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="10">{{ 'Situation10' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.situation == "20" %}
                                <option value="20" selected="selected">{{ 'Situation20' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="20">{{ 'Situation20' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.situation == "30" %}
                                <option value="30" selected="selected">{{ 'Situation30' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="30">{{ 'Situation30' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.situation == "40" %}
                                <option value="40" selected="selected">{{ 'Situation40' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="40">{{ 'Situation40' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.situation == "50" %}
                                <option value="50" selected="selected">{{ 'Situation50' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="50">{{ 'Situation50' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            </select>
                        </div>
                    </div>
                    
                    <div class="well">
                        <legend><h4>{{ 'MainSpecialtyIdentifier' | get_plugin_lang('SepePlugin') | upper }}</h4></legend>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'SpecialtyOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="specialty_origin" value="{{ info.specialty_origin }}" />
                            </div>
                        </div>
                    
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'ProfessionalArea' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="professional_area" value="{{ info.professional_area }}" />
                            </div>
                        </div>
                            
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'SpecialtyCode' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="specialty_code" value="{{ info.specialty_code }}"/>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ 'Duration' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-md-2">
                            <input class="form-control" type="number" name="duration" value="{{ info.duration }}" />
                        </div>
                        <div class="col-md-7 alert alert-info sepe-message-info">    
                            {{ 'NumHoursFormativeAction' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                     <div class="form-group">
                        <label class="col-md-3 control-label">{{ 'StartDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-md-4">
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
                        <div class="col-md-5 alert alert-info sepe-message-info">
                            {{ 'StartDateMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ 'EndDate' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-md-4">
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
                                {% for i in list_year %}
                                    {% if year_end == i %}
                                        <option value="{{ i }}" selected="selected">{{ i }}</option>
                                    {% else %}
                                        <option value="{{ i }}">{{ i }}</option>
                                    {% endif %}
                                {% endfor %}
                            </select>
                        </div>
                        <div class="alert alert-info col-md-5 sepe-message-info">
                            {{ 'EndDateMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ 'FullItineraryIndicator' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-md-2">
                            <select class="form-control" name="full_itinerary_indicator">
                                <option value=""></option>
                                {% if info.full_itinerary_indicator == "SI" %}
                                    <option value="SI" selected="selected">SI</option>
                                {% else %}
                                    <option value="SI">SI</option>
                                {% endif %}
                                {% if info.full_itinerary_indicator == "NO" %}
                                    <option value="NO" selected="selected">NO</option>
                                {% else %}
                                    <option value="NO">NO</option>
                                   {% endif %}
                            </select>
                        </div>
                        <div class="alert alert-info col-md-7 sepe-message-info">
                            {{ 'FullItineraryIndicatorMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ 'FinancingType' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-md-2">
                            <select name="financing_type" class="form-control">
                            <option value=""></option>
                            {% if info.financing_type == "PU" %}
                                <option value="PU" selected="selected">{{ 'Public' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}
                                <option value="PU">{{ 'Public' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            {% if info.financing_type == "PR" %}
                                <option value="PR" selected="selected">{{ 'Private' | get_plugin_lang('SepePlugin') }}</option>
                            {% else %}    
                                <option value="PR">{{ 'Private' | get_plugin_lang('SepePlugin') }}</option>
                            {% endif %}
                            </select>
                         </div>
                        <div class="alert alert-info col-md-7 sepe-message-info">
                            {{ 'FinancingTypeMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ 'AttendeesCount' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-md-2">
                            <input class="form-control" type="number" name="attendees_count" value="{{ info.attendees_count }}" />
                        </div>
                        <div class="alert alert-info col-md-7 sepe-message-info">
                            {{ 'AttendeesCountMessage' | get_plugin_lang('SepePlugin') }}
                        </div>
                    </div>
                    
                    <div class="well">
                        <legend><h4>{{ 'DescriptionAction' | get_plugin_lang('SepePlugin') | upper }}</h4></legend>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'NameAction' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="action_name" value="{{ info.action_name }}" />
                                <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'NameActionMessage' | get_plugin_lang('SepePlugin') }}
                                </div>
                            </div>
                        </div>
                    
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'GlobalInfo' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <textarea class="form-control" name="global_info">{{ info.global_info }}</textarea>
                                 <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'GlobalInfoMessage' | get_plugin_lang('SepePlugin') }}
                                 </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'Schedule' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <textarea class="form-control" name="schedule">{{ info.schedule }}</textarea>
                                 <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'ScheduleMessage' | get_plugin_lang('SepePlugin') }}
                                 </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'Requirements' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <textarea class="form-control" name="requirements">{{ info.requirements }}</textarea>
                                 <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'RequirementsMessage' | get_plugin_lang('SepePlugin') }}
                                 </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ 'ContactAction' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-md-9">
                                <textarea class="form-control" name="contact_action">{{ info.contact_action }}</textarea>
                                 <div class="alert alert-info sepe-message-info sepe-margin-top">
                                    {{ 'ContactActionMessage' | get_plugin_lang('SepePlugin') }}
                                 </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </form>
</div>
