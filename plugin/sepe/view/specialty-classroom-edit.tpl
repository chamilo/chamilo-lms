<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="specialty-classroom-edit.php" method="post" name="form_specialty_action">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Actions' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                    {% if new_classroom == "1" %}
                        <input type="hidden" name="action_id" value="{{ action_id }}" />
                        <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                        <input type="hidden" name="new_classroom" value="1" />
                    {% else %}
                        <input type="hidden" name="action_id" value="{{ action_id }}" />
                        <input type="hidden" name="specialty_id" value="{{ specialty_id }}" />
                        <input type="hidden" name="classroom_id" value="{{ classroom_id }}" />
                        <input type="hidden" name="new_classroom" value="0" />
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
            
            {% if new_classroom == "1" %}
                <div class="well_border">
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'UseExistingCenter' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-9">
                            <select  id="slt_centers_exists" class="chzn-select" style="width:100%" name="slt_centers_exists">
                                <option value="1" selected="selected">{{ 'UseExisting' | get_plugin_lang('SepePlugin') }}</option>
                                <option value="0">{{ 'CreateNewCenter' | get_plugin_lang('SepePlugin') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="well_border" id="centers-list-layer">
                    <fieldset>
                    <legend>{{ 'CenterList' | get_plugin_lang('SepePlugin') }}</legend>
                        <div class="form-group">
                            <label class="control-label col-sm-3">{{ 'Center' | get_plugin_lang('SepePlugin') }}: </label>
                            <div class="col-sm-9">
                                <select  name="exists_center_id" class="chzn-select" style="width:100%">
                                    <option value="" selected="selected"></option>
                                    {% for center in listExistsCenters %}
                                        <option value="{{ center.id }}">{{ center.center_origin }} {{ center.center_code }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="well_border" style="display:none" id="center-data-layer">
            {% else %}
                <div class="well_border" id="center-data-layer">
            {% endif %}
                <fieldset>
                    <legend>{{ 'ClassroomCenter' | get_plugin_lang('SepePlugin') | upper }}</legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'CenterOrigin' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="center_origin" value="{{ info.center_origin }}" />
                        </div>
                    </div>
                        
                    <div class="form-group">
                        <label class="control-label col-sm-3">{{ 'CenterCode' | get_plugin_lang('SepePlugin') }}: </label>
                        <div class="col-sm-3">
                            <input class="form-control" type="text" name="center_code" value="{{ info.center_code }}" />
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </form>
</div>
