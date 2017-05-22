<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-especialidad-tutorials.php" method="post" name="form_specialty_action">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Acciones:</h3></li>
                <li>
                {% if new_tutorial == "SI" %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="new_tutorial" value="SI" />
                {% else %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="cod_tutorial" value="{{ cod_tutorial }}" />
                    <input type="hidden" name="new_tutorial" value="NO" />
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
                <legend>CENTRO PRESENCIAL</legend>
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
               </fieldset>
            
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
  {% for i in list_year %}
  	{% if year_start == i %}
      	<option value="{{ i }}" selected="selected">{{ i }}</option>
    {% else %}
       	<option value="{{ i }}">{{ i }}</option>
    {% endif %}
  {% endfor %}
  </select>
        	           </div>
                       <div class="lert alert-info mensaje_info col-lg-5">Fecha de inicio de la tutoría presencial.</div>
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
                        <div class="alert alert-info mensaje_info col-lg-5">Fecha de finalización de la tutoría presencial.</div>
                </div>
                
                </div>
            
        </div>
    </div>
    </form>
</div>
