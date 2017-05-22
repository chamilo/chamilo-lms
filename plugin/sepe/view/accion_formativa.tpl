<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Opciones:</h3></li>
                <li class="sepe_editar_link">
                    <a href="editar-accion-formativa.php?cod_action={{ cod_action }}">Editar acción</a>
                </li>
                <li class="sepe_borrar_link">
                	<input type="hidden" id="cod_action"  value="{{ cod_action }}"  />
                	<a href="borrar-accion-formativa.php" id="borrar_accion_formativa">Borrar acción</a>
                </li>
                <li class="sepe_listado_link">
                	<a href="listado-acciones-formativas.php">Listado acciones</a>
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
                <legend>Acción Formativa:</legend>
                {% if info != false %}
                <div class="well subcampo">
				<legend class="subcampo">IDENTIFICADOR DE ACCIÓN (ID_ACCION): </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Origen de la acción:</label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.ORIGEN_ACCION }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Código de la acción: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.CODIGO_ACCION }}</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3">Situación: </label>
                    <div class="col-sm-9">
                    	<label class="campo_texto">
                            {% if info.SITUACION == "10" %}
                                10-Solicitada Autorización
                            {% endif %}
                            {% if info.SITUACION == "20" %}
                                20-Programada/Autorizada
                            {% endif %}
                            {% if info.SITUACION == "30" %}
                                30-Iniciada
                            {% endif %}
                            {% if info.SITUACION == "40" %}
                                40-Finalizada
                            {% endif %}
                            {% if info.SITUACION == "50" %}
                                50-Cancelada
                            {% endif %}
                        </label>
                    </div>
                </div>
                
                <div class="well subcampo">
                <legend class="subcampo">IDENTIFICADOR DE ESPECIALIDAD PRINCIPAL</legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Origen de especialidad: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.ORIGEN_ESPECIALIDAD }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Área profesional: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.AREA_PROFESIONAL }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Código de Especialidad: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.CODIGO_ESPECIALIDAD }}</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3">Duración: </label>
                    <div class="col-sm-9">
                    	<label class="campo_texto">
                        {% if info.DURACION > 0 %}
                        	{{ info.DURACION }}
                        {% else %}
                        	<i>Sin especificar</i>
                        {% endif %}    
                         </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Fecha de inicio: </label>
                    <div class="col-sm-9">
                    	<label class="campo_texto">
                        {% if info.FECHA_INICIO == "0000-00-00" %}
                        	<i>Sin especificar</i>
                        {% else %}
                        	{{ fecha_start }}
                        {% endif %}                        
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Fecha de fin: </label>
                    <div class="col-sm-9">
                    	<label class="campo_texto">
                        {% if info.FECHA_FIN == "0000-00-00" %}
                        	<i>Sin especificar</i>
                        {% else %}
                        	{{ fecha_end }}
                        {% endif %} 
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Indicador de itinerario completo: </label>
                    <div class="col-sm-9">
                    	<label class="campo_texto">{{ info.IND_ITINERARIO_COMPLETO }}</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Tipo de Financiación: </label>
                    <div class="col-sm-9">
                    	<label class="campo_texto">
                        {% if info.TIPO_FINANCIACION == "PU" %}
                        	Pública
                        {% endif %}
                        {% if info.TIPO_FINANCIACION == "PR" %}
                        	Privada
                        {% endif %}
                        {% if info.TIPO_FINANCIACION == "" %}
                        	<i>Sin especificar</i>
                        {% endif %}
                        </label>
                    </div>
                </div>
                
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Número de Asistentes: </label>
                    <div class="col-sm-9">
                    	<label class="campo_texto">{{ info.NUMERO_ASISTENTES }}</label>
                    </div>
                </div>
                
                <div class="well subcampo">
                <legend class="subcampo">DESCRIPCION DE LA ACCION FORMATIVA</legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Denominación de la Acción: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.DENOMINACION_ACCION }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Información General: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.INFORMACION_GENERAL }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Horarios: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.HORARIOS }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Requisitos: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.REQUISITOS }}</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Contacto Acción: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ info.CONTACTO_ACCION }}</label>
                        </div>
                    </div>
                </div>
                {% else %}
                	<div class="error-message">No hay información de la acción formativa</div>
                {% endif %}
                </fieldset>
            </form>
         </div>
         <div class="well_border">
            <form class="form-horizontal">
            	<fieldset>
                <legend>Especialidades:
                <a href="editar-especialidad-accion.php?new_specialty=SI&cod_action={{ cod_action }}" class="btn btn-info pull-right">Crear especialidad</a>
                </legend>
                {% for specialty in listSpecialty %}
                	<div class="form-group">
                        <label class="control-label col-sm-3">Especialidad: </label>
                        <div class="col-sm-9">
                            <table width="100%" class="campo_texto">
                            <tr>
                            <td>{{ specialty.ORIGEN_ESPECIALIDAD }} {{ specialty.AREA_PROFESIONAL }} {{ specialty.CODIGO_ESPECIALIDAD }}</td>
                            <td>
                            	<a href="#" class="btn btn-danger btn-sm pull-right mlateral del_specialty" id="specialty{{ specialty.cod }}">Borrar</a>
                            	<a href="editar-especialidad-accion.php?new_specialty=NO&cod_specialty={{ specialty.cod }}&cod_action={{ cod_action }}" class="btn btn-warning btn-sm pull-right mlateral">Editar</a>
                            </td>
                            </tr>
                            </table>
                        </div>
                    </div>
                {% endfor %}                
                </fieldset>
            </form>
         </div>
         
         <div class="well_border">
            <form class="form-horizontal">
            	<fieldset>
                <legend>Participantes:
                <a href="editar-participante-accion.php?new_participant=SI&cod_action={{ cod_action }}" class="btn btn-info pull-right">Crear participante</a>
                </legend>
                {% for participant in listParticipant %}
                	<div class="form-group">
                        <label class="control-label col-sm-3">Participante: </label>
                        <div class="col-sm-9">
                            <table width="100%" class="campo_texto">
                            <tr>
                            <td>{{ participant.firstname }} {{ participant.lastname }} </td>
                            <td>{{ participant.NUM_DOCUMENTO }} {{ participant.LETRA_NIF }} </td>
                            <td>
                            <a href="#" class="btn btn-danger btn-sm pull-right mlateral del_participant" id="participant{{ participant.cod }}">Borrar</a>
                            <a href="editar-participante-accion.php?new_participant=NO&cod_participant={{ participant.cod }}&cod_action={{ cod_action }}" class="btn btn-warning btn-sm pull-right mlateral">Editar</a>
                            </td>
                            </tr>
                            </table>
                        </div>
                    </div>
                {% endfor %} 
                </fieldset>
            </form>
         </div>
    </div>
</div>
