<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-especialidad-classroom.php" method="post" name="form_specialty_action">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Acciones:</h3></li>
                <li>
                {% if new_classroom == "SI" %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="new_classroom" value="SI" />
                {% else %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="cod_classroom" value="{{ cod_classroom }}" />
                    <input type="hidden" name="new_classroom" value="NO" />
                {% endif %}
                <input class="btn btn-primary btn_menu_lateral" type="submit" value="Guardar cambios"  />
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
        
        
        {% if new_classroom == "SI" %}
        <div class="well_border">
        <div class="form-group">
            <label class="control-label col-sm-3">Usar centro existente: </label>
            <div class="col-sm-9">
            <select  id="slt_centro_existente" class="chzn-select" style="width:100%" name="slt_centro_existente">
                <option value="SI" selected="selected">Usar existente</option>
                <option value="NO">Crear nuevo centro</option>
            </select>
            </div>
        </div>
        </div>
        
        <div class="well_border" id="box_listado_centros">
        	<fieldset>
            <legend>Listado de centros</legend>
            	<div class="form-group">
                    <label class="control-label col-sm-3">Centro: </label>
                    <div class="col-sm-9">
                        <select  name="centro_existente" class="chzn-select" style="width:100%">
                        <option value="" selected="selected"></option>
                        {% for centro in listCentrosExistentes %}
                        <option value="{{ centro.cod }}">{{ centro.ORIGEN_CENTRO }} {{ centro.CODIGO_CENTRO }}</option>
                        {% endfor %}
                    	</select>
                    </div>
				</div>
            </fieldset>
        </div>
        <div class="well_border" style="display:none" id="box_datos_centro">
        
        {% else %}
          <div class="well_border" id="box_datos_centro">
        {% endif %}
        	<fieldset>
                <legend>CENTRO PRESENCIAL</legend>
                <div class="form-group">
                    <label class="control-label col-sm-3">Origen del centro: </label>
                    <div class="col-sm-2">
                        <input class="form-control" type="text" name="ORIGEN_CENTRO" value="{{ info.ORIGEN_CENTRO }}" />
                    </div>
                </div>
                    
                <div class="form-group">
                    <label class="control-label col-sm-3">Código del centro: </label>
                    <div class="col-sm-3">
                        <input class="form-control" type="text" name="CODIGO_CENTRO" value="{{ info.CODIGO_CENTRO }}" />
                	</div>
            	</div>
         	</fieldset>
         </div>
    </div>
    </form>
</div>
