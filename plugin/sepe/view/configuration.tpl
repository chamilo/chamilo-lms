<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <form class="form-horizontal" action="configuration.php" method="post" name="configuration_form">
        <div class="col-md-2">&nbsp;</div>
        <div class="col-md-8">
            {% if message_info != "" %}
                <div class="confirmation-message">
                    {{ message_info }}
                </div>
            {% endif %}
            {% if message_error != "" %}
                <div class="error-message">
                    {{ message_error }}
                </div>
            {% endif %}
            <fieldset>
                <legend>{{ 'SepeUser' | get_plugin_lang('SepePlugin') }}</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label">{{ 'ApiKey' | get_plugin_lang('SepePlugin') }}</label>
                    <div class="col-md-7">
                        <input class="form-control" type="text" id="input_key" name="api_key" value="{{ info }}" />
                    </div>
                    <div class="col-md-3">
                        <input type="button" id="key-sepe-generator" class="btn btn-info" value="{{ 'GenerateApiKey' | get_plugin_lang('SepePlugin') }}" />
                    </div>
                </div>
            </fieldset>
        </div>
        <div class="col-md-2">&nbsp;</div>
    </form>
</div>
