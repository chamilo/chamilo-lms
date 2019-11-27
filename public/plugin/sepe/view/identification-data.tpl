<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>{{ 'Options' | get_plugin_lang('SepePlugin') }}:</h3></li>
                <li class="sepe-edit-link">
                    <a href="identification-data-edit.php">
                    {% if info == false %}
                        {{ 'NewCenter' | get_plugin_lang('SepePlugin') }}
                    {% else %}
                        {{ 'EditCenter' | get_plugin_lang('SepePlugin') }}
                    {% endif %}
                    </a>
                </li>
                <li class="sepe-delete-link">
                    <input type="hidden" id="confirmDeleteCenterData" value="{{ 'confirmDeleteCenterData'|get_plugin_lang('SepePlugin') }}" />
                    <a href="identification-data-delete.php" id="delete-center-data">{{ 'DeleteCenter' | get_plugin_lang('SepePlugin') }}</a>
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
                    <legend>{{ 'DataCenter' | get_plugin_lang('SepePlugin') }}</legend>
                    {% if info != false %}
                        <div class="form-group ">
                            <label class="col-sm-3 control-label">{{ 'CenterOrigin' | get_plugin_lang('SepePlugin') }}</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text text-primary">{{ info.center_origin }}</label>
                            </div>
                        </div>

                        <div class="form-group ">
                            <label class="col-sm-3 control-label">{{ 'CenterCode' | get_plugin_lang('SepePlugin') }}</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text text-primary">{{ info.center_code }}</label>
                            </div>
                        </div>
        
                        <div class="form-group ">
                            <label class="col-sm-3 control-label">{{ 'NameCenter' | get_plugin_lang('SepePlugin') }}</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text text-primary">{{ info.center_name }}</label>
                            </div>
                        </div>
        
                        <div class="form-group ">
                            <label class="col-sm-3 control-label">{{ 'PlatformUrl' | get_plugin_lang('SepePlugin') }}</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text text-primary">{{ info.url }}</label>
                            </div>
                        </div>
        
                        <div class="form-group ">
                            <label class="col-sm-3 control-label">{{ 'TrackingUrl' | get_plugin_lang('SepePlugin') }}</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text text-primary">{{ info.tracking_url }}</label>
                            </div>
                        </div>
        
                        <div class="form-group ">
                            <label class="col-sm-3 control-label">{{ 'Phone' | get_plugin_lang('SepePlugin') }}</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text text-primary">{{ info.phone }}</label>
                            </div>
                        </div>
        
                        <div class="form-group ">
                            <label class="col-sm-3 control-label">{{ 'Mail' | get_plugin_lang('SepePlugin') }}</label>
                            <div class="col-sm-9">
                                <label class="sepe-input-text text-primary">{{ info.mail }}</label>
                            </div>
                        </div>
                    {% else %}
                        <div class="alert alert-danger">{{ 'NoIdentificationData' | get_plugin_lang('SepePlugin') }}</div>
                    {% endif %}
                </fieldset>
            </form>
        </div>
    </div>
</div>
