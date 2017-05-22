<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="configuracion.php" method="post" name="form_datos_centro">
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
                <legend>Usuario SEPE</legend>
            	<div class="form-group">
                    <label class="col-md-2 control-label">Clave API</label>
                    <div class="col-md-7">
                    	<input class="form-control" type="text" id="input_key" name="api_key" value="{{ info }}" />
                        
                    </div>
                    <div class="col-md-3">
                    	<input type="button" id="generar_key_sepe" class="btn btn-info" value="Generar api key" />
                    </div>
                </div>
                
                </fieldset>
            
                
            
        
    </div>
        <div class="col-md-2">&nbsp;</div>
    </form>
</div>
