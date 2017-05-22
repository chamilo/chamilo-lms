<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-datos-identificativos.php" method="post" name="form_datos_centro">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Opciones:</h3></li>
                <li>
                    <input class="btn btn-primary btn_menu_lateral" type="submit" value="Guardar cambios"  />
                    <input type="hidden" name="cod" value="{{ info.cod }}" />
                </li>
                <li>
                	<input class="btn btn-warning btn_menu_lateral" type="reset" value="Restablecer"  />
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
                <legend>Datos Identificativos del Centro</legend>
            	
                <div class="form-group">
                    <label class="col-sm-3 control-label">Origen Centro</label>
                    <div class="col-sm-2">
                    	<input type="text" class="form-control" name="origen_centro" value="{{ info.origen_centro }}" />
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Código Centro</label>
                    <div class="col-sm-2">
                    	<input type="text" class="form-control" name="codigo_centro" value="{{ info.codigo_centro }}" />
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Nombre Centro</label>
                    <div class="col-sm-9">
                    	<input type="text" class="form-control" name="nombre_centro" value="{{ info.nombre_centro }}" />
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">URL plataforma</label>
                    <div class="col-sm-9">
                    	<input type="text" class="form-control" name="url" value="{{ info.url }}" style="width:100%"/>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">URL seguimiento</label>
                    <div class="col-sm-9">
                    	<input type="text" class="form-control" name="url_seguimiento" value="{{ info.url_seguimiento }}" style="width:100%" />
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Teléfono</label>
                    <div class="col-sm-3">
                    	<input type="text" class="form-control" name="telefono" value="{{ info.telefono }}" />
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">E-mail</label>
                    <div class="col-sm-3">
                    	<input type="text" class="form-control" name="email" value="{{ info.email }}" />
                    </div>
                </div>
                </fieldset>
            
                
            
        </div>
    </div>
    </form>
</div>
