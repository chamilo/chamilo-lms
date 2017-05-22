<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-especialidad-participante.php" method="post" name="form_specialty_action">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Acciones:</h3></li>
                <li>
                {% if new_specialty == "SI" %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_participant" value="{{ cod_participant }}" />
                    <input type="hidden" name="new_specialty" value="SI" />
                {% else %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="cod_participant" value="{{ cod_participant }}" />
                    <input type="hidden" name="new_specialty" value="NO" />
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
        <div class="well_border">
            	<fieldset>
                <legend>ESPECIALIDADES DEL PARTICIPANTE</legend>
                <div class="well subcampo">
				<legend class="subcampo">IDENTIFICADOR DE ESPECIALIDAD: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Origen de la especialidad: </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="ORIGEN_ESPECIALIDAD" value="{{ info.ORIGEN_ESPECIALIDAD }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Área Profesional: </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="AREA_PROFESIONAL" value="{{ info.AREA_PROFESIONAL }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Código de la Especialidad: </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="CODIGO_ESPECIALIDAD" value="{{ info.CODIGO_ESPECIALIDAD }}" />
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">Fecha de Alta: </label>
                    <div class="col-lg-4">
                    <select name="day_alta" class="form-control slt_fecha">
	<option value=""></option>
    <option value="1" {% if day_alta == "1" %} selected="selected" {% endif %} >01</option>
	<option value="2" {% if day_alta == "2" %} selected="selected" {% endif %} >02</option>
    <option value="3" {% if day_alta == "3" %} selected="selected" {% endif %} >03</option>
    <option value="4" {% if day_alta == "4" %} selected="selected" {% endif %} >04</option>
    <option value="5" {% if day_alta == "5" %} selected="selected" {% endif %} >05</option>
    <option value="6" {% if day_alta == "6" %} selected="selected" {% endif %} >06</option>
    <option value="7" {% if day_alta == "7" %} selected="selected" {% endif %} >07</option>
    <option value="8" {% if day_alta == "8" %} selected="selected" {% endif %} >08</option>
    <option value="9" {% if day_alta == "9" %} selected="selected" {% endif %} >09</option>
    <option value="10" {% if day_alta == "10" %} selected="selected" {% endif %} >10</option>
    <option value="11" {% if day_alta == "11" %} selected="selected" {% endif %} >11</option>
    <option value="12" {% if day_alta == "12" %} selected="selected" {% endif %} >12</option>
    <option value="13" {% if day_alta == "13" %} selected="selected" {% endif %} >13</option>
    <option value="14" {% if day_alta == "14" %} selected="selected" {% endif %} >14</option>
    <option value="15" {% if day_alta == "15" %} selected="selected" {% endif %} >15</option>
    <option value="16" {% if day_alta == "16" %} selected="selected" {% endif %} >16</option>
    <option value="17" {% if day_alta == "17" %} selected="selected" {% endif %} >17</option>
    <option value="18" {% if day_alta == "18" %} selected="selected" {% endif %} >18</option>
    <option value="19" {% if day_alta == "19" %} selected="selected" {% endif %} >19</option>
    <option value="20" {% if day_alta == "20" %} selected="selected" {% endif %} >20</option>
    <option value="21" {% if day_alta == "21" %} selected="selected" {% endif %} >21</option>
    <option value="22" {% if day_alta == "22" %} selected="selected" {% endif %} >22</option>
    <option value="23" {% if day_alta == "23" %} selected="selected" {% endif %} >23</option>
    <option value="24" {% if day_alta == "24" %} selected="selected" {% endif %} >24</option>
    <option value="25" {% if day_alta == "25" %} selected="selected" {% endif %} >25</option>
    <option value="26" {% if day_alta == "26" %} selected="selected" {% endif %} >26</option>
    <option value="27" {% if day_alta == "27" %} selected="selected" {% endif %} >27</option>
    <option value="28" {% if day_alta == "28" %} selected="selected" {% endif %} >28</option>
    <option value="29" {% if day_alta == "29" %} selected="selected" {% endif %} >29</option>
    <option value="30" {% if day_alta == "30" %} selected="selected" {% endif %} >30</option>
    <option value="31" {% if day_alta == "31" %} selected="selected" {% endif %} >31</option>
  </select>
  /
  <select name="month_alta" class="form-control slt_fecha">
	<option value=""></option>
    <option value="1" {% if month_alta == "1" %} selected="selected" {% endif %} >01</option>
	<option value="2" {% if month_alta == "2" %} selected="selected" {% endif %} >02</option>
    <option value="3" {% if month_alta == "3" %} selected="selected" {% endif %} >03</option>
    <option value="4" {% if month_alta == "4" %} selected="selected" {% endif %} >04</option>
    <option value="5" {% if month_alta == "5" %} selected="selected" {% endif %} >05</option>
    <option value="6" {% if month_alta == "6" %} selected="selected" {% endif %} >06</option>
    <option value="7" {% if month_alta == "7" %} selected="selected" {% endif %} >07</option>
    <option value="8" {% if month_alta == "8" %} selected="selected" {% endif %} >08</option>
    <option value="9" {% if month_alta == "9" %} selected="selected" {% endif %} >09</option>
    <option value="10" {% if month_alta == "10" %} selected="selected" {% endif %} >10</option>
    <option value="11" {% if month_alta == "11" %} selected="selected" {% endif %} >11</option>
    <option value="12" {% if month_alta == "12" %} selected="selected" {% endif %} >12</option>
  </select>
  /
  <select name="year_alta" class="form-control slt_fecha">
  	<option value=""></option>
  {% for i in list_year %}
  	{% if year_alta == i %}
      	<option value="{{ i }}" selected="selected">{{ i }}</option>
    {% else %}
       	<option value="{{ i }}">{{ i }}</option>
    {% endif %}
  {% endfor %}
  </select>
  </div>
  <div class="alert alert-info col-lg-5 mensaje_info">Alta para acceder a la especialidad de la acción formativa.
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">Fecha de baja: </label>
                    <div class="col-lg-4">
                    	<select name="day_baja" class="form-control slt_fecha">
	<option value=""></option>
    <option value="1" {% if day_baja == "1" %} selected="selected" {% endif %} >01</option>
	<option value="2" {% if day_baja == "2" %} selected="selected" {% endif %} >02</option>
    <option value="3" {% if day_baja == "3" %} selected="selected" {% endif %} >03</option>
    <option value="4" {% if day_baja == "4" %} selected="selected" {% endif %} >04</option>
    <option value="5" {% if day_baja == "5" %} selected="selected" {% endif %} >05</option>
    <option value="6" {% if day_baja == "6" %} selected="selected" {% endif %} >06</option>
    <option value="7" {% if day_baja == "7" %} selected="selected" {% endif %} >07</option>
    <option value="8" {% if day_baja == "8" %} selected="selected" {% endif %} >08</option>
    <option value="9" {% if day_baja == "9" %} selected="selected" {% endif %} >09</option>
    <option value="10" {% if day_baja == "10" %} selected="selected" {% endif %} >10</option>
    <option value="11" {% if day_baja == "11" %} selected="selected" {% endif %} >11</option>
    <option value="12" {% if day_baja == "12" %} selected="selected" {% endif %} >12</option>
    <option value="13" {% if day_baja == "13" %} selected="selected" {% endif %} >13</option>
    <option value="14" {% if day_baja == "14" %} selected="selected" {% endif %} >14</option>
    <option value="15" {% if day_baja == "15" %} selected="selected" {% endif %} >15</option>
    <option value="16" {% if day_baja == "16" %} selected="selected" {% endif %} >16</option>
    <option value="17" {% if day_baja == "17" %} selected="selected" {% endif %} >17</option>
    <option value="18" {% if day_baja == "18" %} selected="selected" {% endif %} >18</option>
    <option value="19" {% if day_baja == "19" %} selected="selected" {% endif %} >19</option>
    <option value="20" {% if day_baja == "20" %} selected="selected" {% endif %} >20</option>
    <option value="21" {% if day_baja == "21" %} selected="selected" {% endif %} >21</option>
    <option value="22" {% if day_baja == "22" %} selected="selected" {% endif %} >22</option>
    <option value="23" {% if day_baja == "23" %} selected="selected" {% endif %} >23</option>
    <option value="24" {% if day_baja == "24" %} selected="selected" {% endif %} >24</option>
    <option value="25" {% if day_baja == "25" %} selected="selected" {% endif %} >25</option>
    <option value="26" {% if day_baja == "26" %} selected="selected" {% endif %} >26</option>
    <option value="27" {% if day_baja == "27" %} selected="selected" {% endif %} >27</option>
    <option value="28" {% if day_baja == "28" %} selected="selected" {% endif %} >28</option>
    <option value="29" {% if day_baja == "29" %} selected="selected" {% endif %} >29</option>
    <option value="30" {% if day_baja == "30" %} selected="selected" {% endif %} >30</option>
    <option value="31" {% if day_baja == "31" %} selected="selected" {% endif %} >31</option>
  </select>
  /
  <select name="month_baja" class="form-control slt_fecha">
	<option value=""></option>
    <option value="1" {% if month_baja == "1" %} selected="selected" {% endif %} >01</option>
	<option value="2" {% if month_baja == "2" %} selected="selected" {% endif %} >02</option>
    <option value="3" {% if month_baja == "3" %} selected="selected" {% endif %} >03</option>
    <option value="4" {% if month_baja == "4" %} selected="selected" {% endif %} >04</option>
    <option value="5" {% if month_baja == "5" %} selected="selected" {% endif %} >05</option>
    <option value="6" {% if month_baja == "6" %} selected="selected" {% endif %} >06</option>
    <option value="7" {% if month_baja == "7" %} selected="selected" {% endif %} >07</option>
    <option value="8" {% if month_baja == "8" %} selected="selected" {% endif %} >08</option>
    <option value="9" {% if month_baja == "9" %} selected="selected" {% endif %} >09</option>
    <option value="10" {% if month_baja == "10" %} selected="selected" {% endif %} >10</option>
    <option value="11" {% if month_baja == "11" %} selected="selected" {% endif %} >11</option>
    <option value="12" {% if month_baja == "12" %} selected="selected" {% endif %} >12</option>
  </select>
  /
  <select name="year_baja" class="form-control slt_fecha">
  <option value=""></option>
  {% for i in list_year %}
  	{% if year_baja == i %}
      	<option value="{{ i }}" selected="selected">{{ i }}</option>
    {% else %}
       	<option value="{{ i }}">{{ i }}</option>
    {% endif %}
  {% endfor %}
  </select>
  </div>
                        <div class="alert alert-info col-lg-5">Baja para acceder a la especialidad de la acción formativa.
                    </div>
                </div>
                
                
                <div class="well subcampo">
                {% if new_specialty == "SI" %}
					<legend>TUTORÍAS PRESENCIALES: </legend>
                	<div class="alert alert-warning">Debe guardar los cambios antes de crear un centro de tutorias presenciales</div>
                {% else %}
                	<legend>TUTORÍAS PRESENCIALES: 
                	<a href="editar-especialidad-tutorials.php?new_tutorial=SI&cod_specialty={{ info.cod }}&cod_action={{ cod_action }}" class="btn btn-sm btn-info pull-right">Crear tutoria presencial</a>
                	</legend>
                   {% for tutorial in listSpecialtyTutorials %}
                	<div class="form-group">
                        <label class="control-label col-sm-3">Tutoria presencial: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ tutorial.ORIGEN_CENTRO }} {{ tutorial.CODIGO_CENTRO }}
                            <a href="#" class="btn btn-danger btn-sm pull-right mlateral del_classroom" id="tutorial{{ tutorial.cod }}">Borrar</a>
                            <a href="editar-especialidad-tutorials.php?new_tutorial=NO&cod_specialty={{ info.cod }}&cod_tutorial={{ tutorial.cod }}&cod_action={{ cod_action }}" class="btn btn-warning btn-sm pull-right mlateral">Editar</a>
                            </label>
                        </div>
                    </div>
                {% endfor %}
                     
                {% endif %}
                </div>
                
                
                <div class="well subcampo">
				<legend class="subcampo">EVALUACIÓN FINAL: </legend>
                	<div class="well">
					<legend class="subcampo2">CENTRO PRESENCIAL DE EVALUACIÓN FINAL</legend>
                    	<div class="form-group">
                            <label class="control-label col-sm-3">Origen del centro: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="ORIGEN_CENTRO" value="{{ info.ORIGEN_CENTRO }}" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">Código del centro: </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="CODIGO_CENTRO" value="{{ info.CODIGO_CENTRO }}" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                    <label class="control-label col-lg-3">Fecha de Inicio: </label>
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
  {% for i in list_year_2 %}
  	{% if year_start == i %}
      	<option value="{{ i }}" selected="selected">{{ i }}</option>
    {% else %}
       	<option value="{{ i }}">{{ i }}</option>
    {% endif %}
  {% endfor %}
  </select>
  </div>
        	           <div class="alert alert-info col-lg-5 mensaje_info">Fecha de inicio de la evaluación final.
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">Fecha Fin: </label>
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
  {% for i in list_year_2 %}
  	{% if year_end == i %}
      	<option value="{{ i }}" selected="selected">{{ i }}</option>
    {% else %}
       	<option value="{{ i }}">{{ i }}</option>
    {% endif %}
  {% endfor %}
  </select>
  </div>
                        <div class="alert alert-info col-lg-5 mensaje_info">Fecha de finalización de la evaluación final.
                    </div>
                </div>
                
                </div>
                
                <div class="well subcampo">
				<legend class="subcampo">RESULTADOS: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Resultado final: </label>
                        <div class="col-sm-9">
                            <select  name="RESULTADO_FINAL" class="form-control">
                            <option value=""></option>
                            {% if info.RESULTADO_FINAL == "0" %}
                                <option value="0" selected="selected">0 - Iniciado</option>
                            {% else %}
                                <option value="0">0 - Iniciado</option>
                            {% endif %}
                            {% if info.RESULTADO_FINAL == "1" %}
                                <option value="1" selected="selected">1 - Abandona por colocación</option>
                            {% else %}
                                <option value="1">1 - Abandona por colocación</option>
                            {% endif %}
                            {% if info.RESULTADO_FINAL == "2" %}
                                <option value="2" selected="selected">2 - Abandona por otras causas</option>
                            {% else %}
                                <option value="2">2 - Abandona por otras causas</option>
                            {% endif %}
                            {% if info.RESULTADO_FINAL == "3" %}
                                <option value="3" selected="selected">3 - Termina con evaluación positiva</option>
                            {% else %}
                                <option value="3">3 - Termina con evaluación positiva</option>
                            {% endif %}
                            {% if info.RESULTADO_FINAL == "4" %}
                                <option value="4" selected="selected">4 - Termina con evaluación negativa</option>
                            {% else %}
                                <option value="4">4 - Termina con evaluación negativa</option>
                            {% endif %}
                            {% if info.RESULTADO_FINAL == "5" %}
                                <option value="5" selected="selected">5 - Termina sin evaluar</option>
                            {% else %}
                                <option value="5">5 - Termina sin evaluar</option>
                            {% endif %}
                            {% if info.RESULTADO_FINAL == "6" %}
                                <option value="6" selected="selected">6 - Exento</option>
                            {% else %}
                                <option value="6">6 - Exento</option>
                            {% endif %}
                            {% if info.RESULTADO_FINAL == "7" %}
                                <option value="7" selected="selected">7 - Eximido</option>
                            {% else %}
                                <option value="7">7 - Eximido</option>
                            {% endif %}
                            </select>
                            <div class="alert alert-info mensaje_info mtop5">Valor que indica la situación del participante y el resultado logrado por el participante en la especialidad de la acción formativa.<br />
                            Puede tomar los valores de:<br />
                            <ul>
                            <li>0 – Iniciado</li>
                            <li>1 – Abandona por colocación</li>
                            <li>2 – Abandona por otras causas</li>
                            <li>3 – Termina con evaluación positiva</li>
                            <li>4 – Termina con evaluación negativa</li>
                            <li>5 – Termina sin evaluar</li>
                            <li>6 – Exento (de la realización del módulo de formación práctica en centros de trabajo por formación en alternancia con el empleo o por acreditación de la experiencia laboral requerida a tal fin, según lo establecido en el artículo 5bis4 del Real Decreto 34/2008, de 18 de enero).</li>
                            <li>7 – Eximido (de la realización aquellos módulos formativos asociados a unidades de competencia para las que se ha obtenido acreditación, ya sea mediante formación o a través de procesos de reconocimiento de las competencias profesionales adquiridas por la experiencia laboral, regulados en el Real Decreto 1224/2009, de 17 de julio).</li>
                            </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Calificación final: </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="CALIFICACION_FINAL" value="{{ info.CALIFICACION_FINAL }}" />
                            <div class="alert alert-info mensaje_info mtop5">
                            Puntuación obtenida en la prueba de evaluación final del módulo (con independencia de la convocatoria en la que se obtuvo) reflejando, en su caso, las puntuaciones correspondientes a las unidades formativas que lo compongan.<br />
Adopta un valor entre 5 y 10, registrándose con cuatro dígitos para dar cabida a las calificaciones decimales (por ejemplo, la calificación 7,6 debe registrarse como 760).
</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Puntuación final: </label>
                        <div class="col-sm-9">
                            <input class="form-control" class="form-control" type="text" name="PUNTUACION_FINAL" value="{{ info.PUNTUACION_FINAL }}" />
                            
                            <div class="alert alert-info mensaje_info mtop5">Suma de la puntuación media obtenida en la evaluación durante el proceso de aprendizaje, y de la puntuación obtenida en la prueba de evaluación final del módulo, ponderándolas previamente con un peso de 30 por ciento y 70 por ciento, respectivamente.
Adopta un valor entre 5 y 10, sin que pueda ser inferior a 5, ni inferior a la obtenida en la prueba de evaluación final.<br />
Se registra con cuatro dígitos para dar cabida a las puntuaciones decimales (por ejemplo, la puntuación 8,3 debe registrarse como 830).</div>
                        </div>
                    </div>
                </div>
                
                </fieldset>
        </div>
    </div>
    </form>
</div>
