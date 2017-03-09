<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-especialidad-accion.php" method="post" name="form_specialty_action">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Acciones:</h3></li>
                <li>
                {% if new_action == "SI" %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="new_specialty" value="SI" />
                {% else %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="new_specialty" value="NO" />
                {% endif %}
                	<input class="btn btn-primary btn_menu_lateral" type="submit" value="Guardar cambios"  />
                </li>
                <li>
                	<input  class="btn btn-warning btn_menu_lateral" type="reset" value="Restablecer"  />
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
                <legend>Especialidad Acción Formativa</legend>
                <div class="well subcampo">
				<legend class="subcampo">IDENTIFICADOR DE ESPECIALIDAD: </legend>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Origen de la especialidad: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="ORIGEN_ESPECIALIDAD" value="{{ info.ORIGEN_ESPECIALIDAD }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Área Profesional: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="AREA_PROFESIONAL" value="{{ info.AREA_PROFESIONAL }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Código de la Especialidad: </label>
                        <div class="col-sm-3">
                            <input class="form-control" type="text" name="CODIGO_ESPECIALIDAD" value="{{ info.CODIGO_ESPECIALIDAD }}" />
                        </div>
                    </div>
                </div>
                
                <div class="well subcampo">
				<legend class="subcampo">CENTRO DE IMPARTICIÓN: </legend>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Origen del centro: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="ORIGEN_CENTRO" value="{{ info.ORIGEN_CENTRO }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Código del centro: </label>
                        <div class="col-sm-3">
                            <input class="form-control" type="text" name="CODIGO_CENTRO" value="{{ info.CODIGO_CENTRO }}" />
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-lg-3 control-label">Fecha de Inicio: </label>
                    <div class="col-lg-4">
                    <select name="day_start" class="form-control slt_fecha">
                        <option value=""></option>
                        <option value="1" {% if day_start == "1" %} selected="selected" {% endif %} >01</option>
                        <option value="2" {% if day_start == "2" %} selected="selected" {% endif %} >02</option>
                        <option value="3" {% if day_start == "3" %} selected="selected" {% endif %} >03</option>
                        <option value="4" {% if day_start == "4" %} selected="selected" {% endif %} >04</option>
                        <option value="5" {% if day_start == "5" %} selected="selected" {% endif %} >05</option>
                        <option value="6" {% if day_start == "6" %} selected="selected" {% endif %} >06</option>
                        <option value="7" {% if day_start == "7" %} selected="selected" {% endif %} >07</option>
                        <option value="8" {% if day_start == "8" %} selected="selected" {% endif %} >08</option>
                        <option value="9" {% if day_start == "9" %} selected="selected" {% endif %} >09</option>
                        <option value="10" {% if day_start == "10" %} selected="selected" {% endif %} >10</option>
                        <option value="11" {% if day_start == "11" %} selected="selected" {% endif %} >11</option>
                        <option value="12" {% if day_start == "12" %} selected="selected" {% endif %} >12</option>
                        <option value="13" {% if day_start == "13" %} selected="selected" {% endif %} >13</option>
                        <option value="14" {% if day_start == "14" %} selected="selected" {% endif %} >14</option>
                        <option value="15" {% if day_start == "15" %} selected="selected" {% endif %} >15</option>
                        <option value="16" {% if day_start == "16" %} selected="selected" {% endif %} >16</option>
                        <option value="17" {% if day_start == "17" %} selected="selected" {% endif %} >17</option>
                        <option value="18" {% if day_start == "18" %} selected="selected" {% endif %} >18</option>
                        <option value="19" {% if day_start == "19" %} selected="selected" {% endif %} >19</option>
                        <option value="20" {% if day_start == "20" %} selected="selected" {% endif %} >20</option>
                        <option value="21" {% if day_start == "21" %} selected="selected" {% endif %} >21</option>
                        <option value="22" {% if day_start == "22" %} selected="selected" {% endif %} >22</option>
                        <option value="23" {% if day_start == "23" %} selected="selected" {% endif %} >23</option>
                        <option value="24" {% if day_start == "24" %} selected="selected" {% endif %} >24</option>
                        <option value="25" {% if day_start == "25" %} selected="selected" {% endif %} >25</option>
                        <option value="26" {% if day_start == "26" %} selected="selected" {% endif %} >26</option>
                        <option value="27" {% if day_start == "27" %} selected="selected" {% endif %} >27</option>
                        <option value="28" {% if day_start == "28" %} selected="selected" {% endif %} >28</option>
                        <option value="29" {% if day_start == "29" %} selected="selected" {% endif %} >29</option>
                        <option value="30" {% if day_start == "30" %} selected="selected" {% endif %} >30</option>
                        <option value="31" {% if day_start == "31" %} selected="selected" {% endif %} >31</option>
                    </select>
                    /
                    <select name="month_start" class="form-control slt_fecha">
                        <option value=""></option>
                        <option value="1" {% if month_start == "1" %} selected="selected" {% endif %} >01</option>
                        <option value="2" {% if month_start == "2" %} selected="selected" {% endif %} >02</option>
                        <option value="3" {% if month_start == "3" %} selected="selected" {% endif %} >03</option>
                        <option value="4" {% if month_start == "4" %} selected="selected" {% endif %} >04</option>
                        <option value="5" {% if month_start == "5" %} selected="selected" {% endif %} >05</option>
                        <option value="6" {% if month_start == "6" %} selected="selected" {% endif %} >06</option>
                        <option value="7" {% if month_start == "7" %} selected="selected" {% endif %} >07</option>
                        <option value="8" {% if month_start == "8" %} selected="selected" {% endif %} >08</option>
                        <option value="9" {% if month_start == "9" %} selected="selected" {% endif %} >09</option>
                        <option value="10" {% if month_start == "10" %} selected="selected" {% endif %} >10</option>
                        <option value="11" {% if month_start == "11" %} selected="selected" {% endif %} >11</option>
                        <option value="12" {% if month_start == "12" %} selected="selected" {% endif %} >12</option>
                    </select>
                    /
                    <select name="year_start" class="form-control slt_fecha">
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
        	        <div class="alert alert-info mensaje_info col-lg-5">Fecha de inicio de la especialidad formativa.</div>
                </div>
                
                <div class="form-group">
                    <label class="col-lg-3 control-label">Fecha Fin: </label>
                    <div class="col-lg-4">
                    	<select name="day_end" class="form-control slt_fecha">
                            <option value=""></option>
                            <option value="1" {% if day_end == "1" %} selected="selected" {% endif %} >01</option>
                            <option value="2" {% if day_end == "2" %} selected="selected" {% endif %} >02</option>
                            <option value="3" {% if day_end == "3" %} selected="selected" {% endif %} >03</option>
                            <option value="4" {% if day_end == "4" %} selected="selected" {% endif %} >04</option>
                            <option value="5" {% if day_end == "5" %} selected="selected" {% endif %} >05</option>
                            <option value="6" {% if day_end == "6" %} selected="selected" {% endif %} >06</option>
                            <option value="7" {% if day_end == "7" %} selected="selected" {% endif %} >07</option>
                            <option value="8" {% if day_end == "8" %} selected="selected" {% endif %} >08</option>
                            <option value="9" {% if day_end == "9" %} selected="selected" {% endif %} >09</option>
                            <option value="10" {% if day_end == "10" %} selected="selected" {% endif %} >10</option>
                            <option value="11" {% if day_end == "11" %} selected="selected" {% endif %} >11</option>
                            <option value="12" {% if day_end == "12" %} selected="selected" {% endif %} >12</option>
                            <option value="13" {% if day_end == "13" %} selected="selected" {% endif %} >13</option>
                            <option value="14" {% if day_end == "14" %} selected="selected" {% endif %} >14</option>
                            <option value="15" {% if day_end == "15" %} selected="selected" {% endif %} >15</option>
                            <option value="16" {% if day_end == "16" %} selected="selected" {% endif %} >16</option>
                            <option value="17" {% if day_end == "17" %} selected="selected" {% endif %} >17</option>
                            <option value="18" {% if day_end == "18" %} selected="selected" {% endif %} >18</option>
                            <option value="19" {% if day_end == "19" %} selected="selected" {% endif %} >19</option>
                            <option value="20" {% if day_end == "20" %} selected="selected" {% endif %} >20</option>
                            <option value="21" {% if day_end == "21" %} selected="selected" {% endif %} >21</option>
                            <option value="22" {% if day_end == "22" %} selected="selected" {% endif %} >22</option>
                            <option value="23" {% if day_end == "23" %} selected="selected" {% endif %} >23</option>
                            <option value="24" {% if day_end == "24" %} selected="selected" {% endif %} >24</option>
                            <option value="25" {% if day_end == "25" %} selected="selected" {% endif %} >25</option>
                            <option value="26" {% if day_end == "26" %} selected="selected" {% endif %} >26</option>
                            <option value="27" {% if day_end == "27" %} selected="selected" {% endif %} >27</option>
                            <option value="28" {% if day_end == "28" %} selected="selected" {% endif %} >28</option>
                            <option value="29" {% if day_end == "29" %} selected="selected" {% endif %} >29</option>
                            <option value="30" {% if day_end == "30" %} selected="selected" {% endif %} >30</option>
                            <option value="31" {% if day_end == "31" %} selected="selected" {% endif %} >31</option>
                       	</select>
                        /
                        <select name="month_end" class="form-control slt_fecha">
                            <option value=""></option>
                            <option value="1" {% if month_end == "1" %} selected="selected" {% endif %} >01</option>
                            <option value="2" {% if month_end == "2" %} selected="selected" {% endif %} >02</option>
                            <option value="3" {% if month_end == "3" %} selected="selected" {% endif %} >03</option>
                            <option value="4" {% if month_end == "4" %} selected="selected" {% endif %} >04</option>
                            <option value="5" {% if month_end == "5" %} selected="selected" {% endif %} >05</option>
                            <option value="6" {% if month_end == "6" %} selected="selected" {% endif %} >06</option>
                            <option value="7" {% if month_end == "7" %} selected="selected" {% endif %} >07</option>
                            <option value="8" {% if month_end == "8" %} selected="selected" {% endif %} >08</option>
                            <option value="9" {% if month_end == "9" %} selected="selected" {% endif %} >09</option>
                            <option value="10" {% if month_end == "10" %} selected="selected" {% endif %} >10</option>
                            <option value="11" {% if month_end == "11" %} selected="selected" {% endif %} >11</option>
                            <option value="12" {% if month_end == "12" %} selected="selected" {% endif %} >12</option>
                        </select>
                        /
                        <select name="year_end" class="form-control slt_fecha">
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
                    <div class="col-lg-5 mensaje_info alert alert-info">Fecha de finalización de especialidad formativa.</div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Modalidad de impartición: </label>
                    <div class="col-sm-9">
                    	<select  name="MODALIDAD_IMPARTICION" class="chzn-select">
                        <option value=""></option>
                        {% if info.MODALIDAD_IMPARTICION == "TF" %}
                        	<option value="TF" selected="selected">Teleformación</option>
                        {% else %}
                        	<option value="TF">Teleformación</option>
                        {% endif %}
                        {% if info.MODALIDAD_IMPARTICION == "PR" %}
                        	<option value="PR" selected="selected">Presencial</option>
                        {% else %}
                        	<option value="PR">Presencial</option>
                        {% endif %}
                        {% if info.MODALIDAD_IMPARTICION == "PE" %}
                        	<option value="PE" selected="selected">Práctica no laboral (formación) en centro de trabajo</option>
                        {% else %}
                        	<option value="PE">Práctica no laboral (formación) en centro de trabajo</option>
                        {% endif %}
                        </select>
                        <em class="alert alert-info mensaje_info mtop5">Modo de impartición de la especialidad formativa de la acción.</em>
                    </div>
                </div>
                
                <div class="well subcampo">
				<legend class="subcampo">DATOS DE DURACIÓN: </legend>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Horas presenciales: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HORAS_PRESENCIAL" value="{{ info.HORAS_PRESENCIAL }}" />
                        </div>
                        <div class="col-sm-7 alert alert-info mensaje_info">Número de horas realizadas de forma presencial.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Horas teleformación: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HORAS_TELEFORMACION" value="{{ info.HORAS_TELEFORMACION }}" />
                        </div>
                        <div class="col-sm-7 alert alert-info mensaje_info">Número de horas realizadas a través de teleformación.</div>
                    </div>
                </div>
                
                <div class="well subcampo">
                {% if new_action == "SI" %}
					<legend>CENTROS DE SESIONES PRESENCIALES: </legend>
                	<div class="alert alert-warning">Debe guardar los cambios antes de crear un centro presencial</div>
                {% else %}
                	<legend>CENTROS DE SESIONES PRESENCIALES: 
                	<a href="editar-especialidad-classroom.php?new_classroom=SI&cod_specialty={{ info.cod }}&cod_action={{ cod_action }}" class="btn btn-sm btn-info pull-right">Crear centro presencial</a>
                	</legend>
                   {% for classroom in listClassroom %}
                	<div class="form-group">
                        <label class="col-sm-3 control-label">Centro presencial: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ classroom.ORIGEN_CENTRO }} {{ classroom.CODIGO_CENTRO }}
                            <a href="#" class="btn btn-danger btn-sm pull-right mlateral del_classroom" id="classroom{{ classroom.cod }}">Borrar</a>
                            <a href="editar-especialidad-classroom.php?new_classroom=NO&cod_specialty={{ info.cod }}&cod_classroom={{ classroom.cod }}&cod_action={{ cod_action }}" class="btn btn-warning btn-sm pull-right mlateral">Editar</a>
                            </label>
                        </div>
                    </div>
                {% endfor %}
                     
                {% endif %}
                </div>
                
                <div class="well subcampo">
                {% if new_action == "SI" %}
					<legend>TUTORES-FORMADORES: </legend>
                	<div class="alert alert-warning">Debe guardar los cambios antes de crear un centro presencial</div>
                {% else %}
                	<legend>TUTORES-FORMADORES:
                	<a href="editar-especialidad-tutor.php?new_tutor=SI&cod_specialty={{ info.cod }}&cod_action={{ cod_action }}" class="btn btn-sm btn-info pull-right">Crear tutor-formador</a>
                	</legend>
                    {% for tutor in listTutors %}
                	<div class="form-group">
                        <label class="col-sm-3 control-label">Tutor-formador: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">
                            {{ tutor.firstname }} {{ tutor.lastname }}
                             ( {{ tutor.NUM_DOCUMENTO }}-{{ tutor.LETRA_NIF }} )
                            <a href="#" class="btn btn-danger btn-sm pull-right mlateral del_tutor" id="tutor{{ tutor.cod }}">Borrar</a>
                            <a href="editar-especialidad-tutor.php?new_tutor=NO&cod_specialty={{ info.cod }}&cod_tutor={{ tutor.cod }}&cod_action={{ cod_action }}" class="btn btn-warning btn-sm pull-right mlateral">Editar</a>
                            </label>
                        </div>
                    </div>
                	{% endfor %}
                    
                {% endif %}
                
                </div>
                
                
                <div class="well subcampo">
                <legend class="subcampo">USO DEL CONTENIDO</legend>
                    <div class="well">
					<legend class="subcampo2">HORARIO MAÑANA</legend>
                    <div class="alert alert-info mensaje_info">Se considerará el período temporal comprendido entre las 7:00 y las 15:00 horas.</div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Nº de participantes: </label>
                    	<div class="col-sm-2">
                    	  <input class="form-control" type="number" name="HM_NUM_PARTICIPANTES" value="{{ info.HM_NUM_PARTICIPANTES }}" />
                   		</div>
                	</div>
                
                	<div class="form-group">
                        <label class="col-sm-3 control-label">Número de accesos: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HM_NUMERO_ACCESOS" value="{{ info.HM_NUMERO_ACCESOS }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Duración Total: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HM_DURACION_TOTAL" value="{{ info.HM_DURACION_TOTAL }}"/>
                        </div>
                    </div>
                    </div>
                    <hr />
                    
                    <div class="well">
					<legend class="subcampo2">HORARIO TARDE</legend>
                    <div class="alert alert-info mensaje_info">Se considerará el período temporal comprendido entre las 15:00 horas y las 23:00 horas.</div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Nº de participantes: </label>
                    	<div class="col-sm-2">
                    	  <input class="form-control" type="number" name="HT_NUM_PARTICIPANTES" value="{{ info.HT_NUM_PARTICIPANTES }}" />
                   		</div>
                	</div>
                
                	<div class="form-group">
                        <label class="col-sm-3 control-label">Número de accesos: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HT_NUMERO_ACCESOS" value="{{ info.HT_NUMERO_ACCESOS }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Duración Total: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HT_DURACION_TOTAL" value="{{ info.HT_DURACION_TOTAL }}"/>
                        </div>
                    </div>
                    </div>
                    <hr />
                    
                    
                    <div class="well">
					<legend class="subcampo2">HORARIO NOCHE</legend>
                    <div class="alert alert-info mensaje_info">Se considerará el período temporal comprendido entre las 23:00 horas y las 7:00 horas.</div> 
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Nº de participantes: </label>
                    	<div class="col-sm-2">
                    	  <input class="form-control" type="number" name="HN_NUM_PARTICIPANTES" value="{{ info.HN_NUM_PARTICIPANTES }}" />
                   		</div>
                	</div>
                
                	<div class="form-group">
                        <label class="col-sm-3 control-label">Número de accesos: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HN_NUMERO_ACCESOS" value="{{ info.HN_NUMERO_ACCESOS }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Duración Total: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="HN_DURACION_TOTAL" value="{{ info.HN_DURACION_TOTAL }}"/>
                        </div>
                    </div>
                    </div>
                    <hr />
                    
                    <div class="well">
					<legend class="subcampo2">SEGUIMIENTO Y EVALUACIÓN</legend>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Nº de participantes: </label>
                    	<div class="col-sm-2">
                    	  <input class="form-control" type="number" name="NUM_PARTICIPANTES" value="{{ info.NUM_PARTICIPANTES }}" />
                   		</div>
                	</div>
                
                	<div class="form-group">
                        <label class="col-sm-3 control-label">Número de actividades de aprendizaje: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="NUMERO_ACTIVIDADES_APRENDIZAJE" value="{{ info.NUMERO_ACTIVIDADES_APRENDIZAJE }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Número de intentos: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="NUMERO_INTENTOS" value="{{ info.NUMERO_INTENTOS }}"/>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Número de actividades de evaluación: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="number" name="NUMERO_ACTIVIDADES_EVALUACION" value="{{ info.NUMERO_ACTIVIDADES_EVALUACION }}"/>
                        </div>
                    </div>
                    </div>
                    <hr />
                    
                    
                </div>
                
                </fieldset>
            
                
            
        </div>
    </div>
    </form>
</div>
