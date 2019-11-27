<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="specialty-tutorial-edit.php" method="post" name="form_specialty_action">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Actions' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                        {% if new_tutorial == "1" %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                            <input type="hidden" name="new_tutorial" value="1" />
                        {% else %}
                            <input type="hidden" name="action_id" value="{{ action_id }}" />
                            <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                            <input type="hidden" name="tutorial_id" value="{{ tutorial_id }}" />
                            <input type="hidden" name="new_tutorial" value="0" />
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
                    <legend>{{ 'ClassroomCenter' | get_plugin_lang('SepePlugin') | upper }}</legend>
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
                </fieldset>
                
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
                        {{ 'StartDateMessageTutorial' | get_plugin_lang('SepePlugin') }}
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
                    <div class="alert alert-info sepe-message-info col-lg-5">
                        {{ 'EndDateMessageTutorial' | get_plugin_lang('SepePlugin') }}
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
