<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-accion-formativa.php" method="post" name="form_datos_centro">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Acciones:</h3></li>
                <li>
                {% if new_action == "SI" %}
                	<input type="hidden" name="cod_action" value="NO" />
                    <input type="hidden" name="id_course" value="{{ id_course }}" />
                {% else %}
                	<input type="hidden" name="cod_action" value="{{ info.cod }}" />
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
        {% if rmessage == "YES" %}
            <div class="{{ class }}">
                {{ responseMessage }}
            </div>
        {% endif %}
        <div class="well_border">
            	<fieldset>
                <legend>Acción Formativa</legend>
                <div class="well subcampo">
				<legend class="subcampo">IDENTIFICADOR DE ACCIÓN (ID_ACCION): </legend>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Origen de la acción: </label>
                        <div class="col-md-2">
                            <input class="form-control" type="text" name="ORIGEN_ACCION" value="{{ info.ORIGEN_ACCION }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">Código de la acción: </label>
                        <div class="col-md-2">
                            <input class="form-control" type="text" name="CODIGO_ACCION" value="{{ info.CODIGO_ACCION }}" />
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label">Situación: </label>
                    <div class="col-md-9">
                    	<select  name="SITUACION" class="form-control">
                        <option value=""></option>
                        {% if info.SITUACION == "10" %}
                        	<option value="10" selected="selected">10-Solicitada Autorización</option>
                        {% else %}
                        	<option value="10">10-Solicitada Autorización</option>
                        {% endif %}
                        {% if info.SITUACION == "20" %}
                        	<option value="20" selected="selected">20-Programada/Autorizada</option>
                        {% else %}
                        	<option value="20">20-Programada/Autorizada</option>
                        {% endif %}
                        {% if info.SITUACION == "30" %}
                        	<option value="30" selected="selected">30-Iniciada</option>
                        {% else %}
                        	<option value="30">30-Iniciada</option>
                        {% endif %}
                        {% if info.SITUACION == "40" %}
                        	<option value="40" selected="selected">40-Finalizada</option>
                        {% else %}
                        	<option value="40">40-Finalizada</option>
                        {% endif %}
                        {% if info.SITUACION == "50" %}
                        	<option value="50" selected="selected">50-Cancelada</option>
                        {% else %}
                        	<option value="50">50-Cancelada</option>
                        {% endif %}
                        </select>
                    </div>
                </div>
                
                <div class="well subcampo">
                <legend class="subcampo">IDENTIFICADOR DE ESPECIALIDAD PRINCIPAL</legend>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Origen de especialidad: </label>
                    <div class="col-md-9">
                    	<input class="form-control" type="text" name="ORIGEN_ESPECIALIDAD" value="{{ info.ORIGEN_ESPECIALIDAD }}" />
                    </div>
                </div>
                
                <div class="form-group">
                        <label class="col-md-3 control-label">Área profesional: </label>
                        <div class="col-md-9">
                            <input class="form-control" type="text" name="AREA_PROFESIONAL" value="{{ info.AREA_PROFESIONAL }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">Código de Especialidad: </label>
                        <div class="col-md-9">
                            <input class="form-control" type="text" name="CODIGO_ESPECIALIDAD" value="{{ info.CODIGO_ESPECIALIDAD }}"/>
                        </div>
                    </div>
                </div>
                
                
                <div class="form-group">
                    <label class="col-md-3 control-label">Duración: </label>
                    <div class="col-md-2">
                    	<input class="form-control" type="number" name="DURACION" value="{{ info.DURACION }}" />
                    </div>
                    <div class="col-md-7 alert alert-info mensaje_info">    
                        Número de horas de la acción formativa.
                    </div>
                </div>
                
                 <div class="form-group">
                    <label class="col-md-3 control-label">Fecha de Inicio: </label>
                    <div class="col-md-4">
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
  					<div class="col-md-5 alert alert-info mensaje_info">Fecha de inicio de la acción formativa.</div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label">Fecha Fin: </label>
                    <div class="col-md-4">
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
                    <div class="alert alert-info col-md-5 mensaje_info">Fecha de finalización de la acción formativa.</div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label">Ind. de itinerario completo: </label>
                    <div class="col-md-2">
                    <select class="form-control" name="IND_ITINERARIO_COMPLETO">
                    	<option value=""></option>
                    	{% if info.IND_ITINERARIO_COMPLETO == "SI" %}
                    		<option value="SI" selected="selected">SI</option>
                        {% else %}
                        	<option value="SI">SI</option>
                        {% endif %}
                        {% if info.IND_ITINERARIO_COMPLETO == "NO" %}
                        	<option value="NO" selected="selected">NO</option>
                        {% else %}
                        	<option value="NO">NO</option>
                       	{% endif %}
                    </select>
                    </div>
                    <div class="alert alert-info col-md-7 mensaje_info">Indica si la acción formativa se imparte de forma completa.</div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label">Tipo de Financiación: </label>
                    <div class="col-md-2">
                    <select name="TIPO_FINANCIACION" class="form-control">
                    <option value=""></option>
                    {% if info.TIPO_FINANCIACION == "PU" %}
                        <option value="PU" selected="selected">Pública</option>
                    {% else %}
                    	<option value="PU">Pública</option>
                    {% endif %}
                    {% if info.TIPO_FINANCIACION == "PR" %}
                        <option value="PR" selected="selected">Privada</option>
                    {% else %}    
                        <option value="PR">Privada</option>
                    {% endif %}
                    </select>
                     </div>
                    <div class="alert alert-info col-md-7 mensaje_info">Procedencia de la dotación económica.
                    
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label">Número de asistentes: </label>
                    <div class="col-md-2">
                    	<input class="form-control" type="number" name="NUMERO_ASISTENTES" value="{{ info.NUMERO_ASISTENTES }}" />
                        </div>
                    <div class="alert alert-info col-md-7 mensaje_info">Número de plazas ofertadas.
                    </div>
                </div>
                
                
                <div class="well subcampo">
                <legend class="subcampo">DESCRIPCION DE LA ACCION FORMATIVA</legend>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Denominación de la Acción: </label>
                        <div class="col-md-9">
                   	    	<input class="form-control" type="text" name="DENOMINACION_ACCION" value="{{ info.DENOMINACION_ACCION }}" />
                            <div class="alert alert-info mensaje_info mtop5">Nombre o descripción breve de la acción formativa.</div>
                        </div>
                    </div>
                
                    <div class="form-group">
                        <label class="col-md-3 control-label">Información General: </label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="INFORMACION_GENERAL">{{ info.INFORMACION_GENERAL }}</textarea>
                             <div class="alert alert-info mensaje_info mtop5">Breve texto descriptivo de los objetivos, contenidos y estructura de la acción formativa.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">Horarios: </label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="HORARIOS">{{ info.HORARIOS }}</textarea>
                             <div class="alert alert-info mensaje_info mtop5">Breve texto que señala el período temporal durante el que se desarrolla la acción formativa.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">Requisitos: </label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="REQUISITOS">{{ info.REQUISITOS }}</textarea>
                             <div class="alert alert-info mensaje_info mtop5">Breve texto que especifica los requisitos de acceso a la formación.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">Contacto Acción: </label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="CONTACTO_ACCION">{{ info.CONTACTO_ACCION }}</textarea>
                             <div class="alert alert-info mensaje_info mtop5">Teléfono, sitio web o dirección de correo electrónico a través de los que obtener información específica y detallada sobre la acción formativa.</div>
                        </div>
                    </div>
                </div>
                
                </fieldset>
            
                
            
        </div>
    </div>
    </form>
</div>
