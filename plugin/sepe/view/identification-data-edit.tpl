<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="identification-data-edit.php" method="post" name="form_data_center">
        <div class="col-md-3">
            <div id="course_category_well" class="well">
                <ul class="nav nav-list">
                    <li class="nav-header"><h3>{{ 'Options' | get_plugin_lang('SepePlugin') }}:</h3></li>
                    <li>
                        <input class="btn btn-primary sepe-btn-menu-side" type="submit" value="{{ 'SaveChanges' | get_plugin_lang('SepePlugin') }}"  />
                        <input type="hidden" name="id" value="{{ info.id }}" />
                        <input type="hidden" name="sec_token" value="{{ sec_token }}" />
                    </li>
                    <li>
                        <input class="btn btn-warning sepe-btn-menu-side" type="reset" value="{{ 'Reset' | get_plugin_lang('SepePlugin') }}" />
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
            <div class="well_border span8">
                <fieldset>
                    <legend>{{ 'DataCenter' | get_plugin_lang('SepePlugin') }}</legend>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'CenterOrigin' | get_plugin_lang('SepePlugin') }}</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" name="center_origin" value="{{ info.center_origin }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'CenterCode' | get_plugin_lang('SepePlugin') }}</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" name="center_code" value="{{ info.center_code }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'NameCenter' | get_plugin_lang('SepePlugin') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="center_name" value="{{ info.center_name }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'PlatformUrl' | get_plugin_lang('SepePlugin') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="url" value="{{ info.url }}" style="width:100%"/>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'TrackingUrl' | get_plugin_lang('SepePlugin') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="tracking_url" value="{{ info.tracking_url }}" style="width:100%" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'Phone' | get_plugin_lang('SepePlugin') }}</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" name="phone" value="{{ info.phone }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ 'Mail' | get_plugin_lang('SepePlugin') }}</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" name="mail" value="{{ info.mail }}" />
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </form>
</div>
