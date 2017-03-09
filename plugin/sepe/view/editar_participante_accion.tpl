<script type='text/javascript' src="../js/sepe.js"></script>
<script type='text/javascript'>
	$(document).ready(function () {
		$("select[name='cod_tutor_empresa']").change(function(){
			if($(this).val() == "nuevo_tutor_empresa"){
				$("#box_nuevo_tutor_empresa").show();
			}else{
				$("#box_nuevo_tutor_empresa").hide();
			}
		});
		
		$("select[name='cod_tutor_formacion']").change(function(){
			if($(this).val() == "nuevo_tutor_formacion"){
				$("#box_nuevo_tutor_formacion").show();
			}else{
				$("#box_nuevo_tutor_formacion").hide();
			}
		});
	});
</script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-participante-accion.php" method="post" name="form_participant_action">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Acciones:</h3></li>
                <li>
                {% if new_participant == "SI" %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="new_participant" value="SI" />
                {% else %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_participant" value="{{ cod_participant }}" />
                    <input type="hidden" name="new_participant" value="NO" />
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
                <legend>Participante Acción Formativa</legend>
		  <div class="well subcampo">
		  <legend class="subcampo">LISTADO DE USUARIOS DEL CURSO CHAMILO: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Alumno: </label>
                        <div class="col-sm-9">
                        <select name="cod_user_chamilo" id="cod_user_chamilo" class="form-control">
                        
                         
                         {% if info_user_chamilo is empty %}
                         	<option value="" selected="selected"></option>
                         {% else %}
                         	<option value=""></option>
                         	<option value="{{ info_user_chamilo.user_id }}" selected="selected">{{ info_user_chamilo.firstname }} {{ info_user_chamilo.lastname }}</option>
                         {% endif %}
                         
                         
                        {% for alumno in listAlumno %}
                        	<option value="{{ alumno.user_id }}">{{ alumno.firstname }} {{ alumno.lastname }}</option>
                        {% endfor %}
                        </select>
                           
                        </div>
                    </div>
                </div>
			

                <div class="well subcampo">

				<legend class="subcampo">IDENTIFICADOR PARTICIPANTE: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Tipo de documento: </label>
                        <div class="col-sm-9">
                        <select  name="TIPO_DOCUMENTO" class="form-control">
                        <option value=""></option>
                        {% if info.TIPO_DOCUMENTO == "D" %}
                        	<option value="D" selected="selected">D - Documento Nacional de Identidad (DNI)</option>
                        {% else %}
                        	<option value="D">D - Documento Nacional de Identidad (DNI).</option>
                        {% endif %}
                        {% if info.TIPO_DOCUMENTO == "E" %}
                        	<option value="E" selected="selected">E - Número de Identificador de Extranjero (NIE)</option>
                        {% else %}
                        	<option value="E">E - Número de Identificador de Extranjero (NIE)</option>
                        {% endif %}
                        {% if info.TIPO_DOCUMENTO == "U" %}
                        	<option value="U" selected="selected">U - Identificación convencional para ciudadanos del Espacio Económico Europeo sin NIE</option>
                        {% else %}
                        	<option value="U">U - Identificación convencional para ciudadanos del Espacio Económico Europeo sin NIE</option>
                        {% endif %}
                        {% if info.TIPO_DOCUMENTO == "G" %}
                        	<option value="G" selected="selected">G - Personas privadas de libertad</option>
                        {% else %}
                        	<option value="G">G - Personas privadas de libertad</option>
                        {% endif %}

                       {% if info.TIPO_DOCUMENTO == "W" %}
                        	<option value="W" selected="selected">W - Identificación convencional para ciudadanos que no pertenecen Espacio Económico Europeo y sin NIE</option>
                        {% else %}
                        	<option value="W">W - Identificación convencional para ciudadanos que no pertenecen Espacio Económico Europeo y sin NIE</option>
                        {% endif %}
                        {% if info.TIPO_DOCUMENTOO == "H" %}
                        	<option value="H" selected="selected">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% else %}
                        	<option value="H">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% endif %}
                        </select>
                            
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Número de documento: </label>
                        <div class="col-sm-3">
                            <input class="form-control" type="text" name="NUM_DOCUMENTO" value="{{ info.NUM_DOCUMENTO }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Letra NIF: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="LETRA_NIF" value="{{ info.LETRA_NIF }}" />
                        </div>
                    </div>
                    
                     <div class="alert alert-warning">
                        El campo de "Número del documento" tiene una longitud de 10 caracteres alfanuméricos.
                        <table id="tabla_info_nif">
                        <tr><th>Tipo</th><th>Número</th><th>Carácter de control NIF</th></tr>
                        <tr><td>D</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>E</td><td>bbXN7<br />bbYN7<br />bbZN7</td><td>L<br />L<br />L</td></tr>
                        <tr><td>U</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>W</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>G</td><td>N10</td><td>L</td></tr>
                        <tr><td>H</td><td>bbN8</td><td>L</td></tr>
                        </table>
                     </div>
                    
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Indicador de competencias clave: </label>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="INDICADOR_COMPETENCIAS_CLAVE" value="{{ info.INDICADOR_COMPETENCIAS_CLAVE }}" />
                    </div>
                </div>
                <div class="well subcampo">
				<legend class="subcampo">CONTRATO FORMACION: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">ID contrato CFA: </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="ID_CONTRATO_CFA" value="{{ info.ID_CONTRATO_CFA }}" />
                            <em class="alert alert-info mensaje_info mtop5">Dato alfanumérico de 14 posiciones formado por la concatenación de:<br />
                            <ul>
                            <li> 1 posición alfabética que indica el organismo que asignó identificador al contrato. En la actualidad siempre “E” estatal.</li>
                            <li> 2 posiciones numéricas con el código de la provincia.</li>
                            <li> 4 posiciones numéricas con el año del contrato.</li>
                            <li> 7 posiciones numéricas con el número secuencial asignado al contrato en la provincia y año.</li></ul></em>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">CIF empresa: </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="CIF_EMPRESA" value="{{ info.CIF_EMPRESA }}" />
                        </div>
                    </div>
                    
                    <div class="well">
					<legend class="subcampo2">ID TUTOR EMPRESA: </legend>
                   	<div class="form-group">
                       	<label class="control-label col-sm-3">Listado tutores empresa</label>
                           <div class="col-sm-9">
                           <select name="cod_tutor_empresa" class="form-control">
                           	<option value="nuevo_tutor_empresa">Crear nuevo tutor empresa</option>
                               {% for tutor in listTutorE %}
                               	{% if tutor.cod == info.cod_tutor_empresa or ( info|length == 0 and tutor.cod == "1" ) %}
                                    	<option value="{{ tutor.cod }}" selected="selected">{{ tutor.alias }}</option>
                                    {% else %}
                                    	<option value="{{ tutor.cod }}">{{ tutor.alias }}</option>   
                                    {% endif %}
                                {% endfor %}
                            </select>
                            </div>
                        </div>
                    	
                        
                        	<div id="box_nuevo_tutor_empresa" style="display:none">

                        <div class="form-group">
                        	<label class="control-label col-sm-3">Nombre</label>
                            <div class="col-sm-9">
                            	<input class="form-control" type="text" name="TE_alias" value="" />
                            </div>
                        </div>                      
                        
                     	<div class="form-group">
                            <label class="control-label col-sm-3">Tipo de documento: </label>
                            <div class="col-sm-9">
                       
                            <select  name="TE_TIPO_DOCUMENTO" class="form-control">
                       	<option value=""></option>
                        {% if info.TE_TIPO_DOCUMENTO == "D" %}
                        	<option value="D" selected="selected">D - Documento Nacional de Identidad (DNI)</option>
                        {% else %}
                        	<option value="D">D - Documento Nacional de Identidad (DNI).</option>
                        {% endif %}
                        {% if info.TE_TIPO_DOCUMENTO == "E" %}
                        	<option value="E" selected="selected">E - Número de Identificador de Extranjero (NIE)</option>
                        {% else %}
                        	<option value="E">E - Número de Identificador de Extranjero (NIE)</option>
                        {% endif %}
                        {% if info.TE_TIPO_DOCUMENTO == "U" %}
                        	<option value="U" selected="selected">U - Identificación convencional para ciudadanos del Espacio Económico Europeo sin NIE</option>
                        {% else %}
                        	<option value="U">U - Identificación convencional para ciudadanos del Espacio Económico Europeo sin NIE</option>
                        {% endif %}
                        {% if info.TE_TIPO_DOCUMENTO == "G" %}
                        	<option value="G" selected="selected">G - Personas privadas de libertad</option>
                        {% else %}
                        	<option value="G">G - Personas privadas de libertad</option>
                        {% endif %}
                        {% if info.TE_TIPO_DOCUMENTO == "W" %}
                        	<option value="W" selected="selected">W - Identificación convencional para ciudadanos que no pertenecen Espacio Económico Europeo y sin NIE</option>
                        {% else %}
                        	<option value="W">W - Identificación convencional para ciudadanos que no pertenecen Espacio Económico Europeo y sin NIE</option>
                        {% endif %}
                        {% if info.TE_TIPO_DOCUMENTOO == "H" %}
                        	<option value="H" selected="selected">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% else %}
                        	<option value="H">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% endif %}
                        </select>
                        	</div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-3">Número de documento: </label>
	                        <div class="col-sm-3">
                                <input class="form-control" type="text" name="TE_NUM_DOCUMENTO" value="" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">Letra NIF: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="text" name="TE_LETRA_NIF" value="" />
                            </div>
                        </div>
                    	
                        <div class="alert alert-warning mensaje_info">
                        El campo de "Número del documento" tiene una longitud de 10 caracteres alfanuméricos.
                        <table id="tabla_info_nif">
                        <tr><th>Tipo</th><th>Número</th><th>Carácter de control NIF</th></tr>
                        <tr><td>D</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>E</td><td>bbXN7<br />bbYN7<br />bbZN7</td><td>L<br />L<br />L</td></tr>
                        <tr><td>U</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>W</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>G</td><td>N10</td><td>L</td></tr>
                        <tr><td>H</td><td>bbN8</td><td>L</td></tr>
                        </table>
                        </div>
                    </div>
                    </div>
                    
                    
                    <div class="well">
					<legend class="subcampo2">ID TUTOR FORMACIÓN: </legend>
						<div class="form-group">
                        	<label class="control-label col-sm-3">Listado tutores formación</label>
                            <div class="col-sm-9">
                            <select name="cod_tutor_formacion" class="form-control">
                            	<option value="nuevo_tutor_formacion">Crear nuevo tutor formación</option>
                                {% for tutor in listTutorF %}
                                	{% if tutor.cod == info.cod_tutor_formacion or ( info|length == 0 and tutor.cod == "1" ) %}
                                    	<option value="{{ tutor.cod }}" selected="selected">{{ tutor.alias }}</option>
                                    {% else %}
                                    	<option value="{{ tutor.cod }}">{{ tutor.alias }}</option>   
                                    {% endif %}
                                {% endfor %}
                            </select>
                            </div>
                        </div>
                    
                        
                            <div id="box_nuevo_tutor_formacion" style="display:none">
                        
                        <div class="form-group">
                        	<label class="control-label col-sm-3">Nombre</label>
                            <div class="col-sm-9">
                            	<input class="form-control" type="text" name="TF_alias" value="" />
                            </div>
                        </div> 
                        
                       <div class="form-group">
                            <label class="control-label col-sm-3">Tipo de documento: </label>
                            <div class="col-sm-9">
                            <select  name="TF_TIPO_DOCUMENTO" class="form-control">
                        <option value=""></option>
                        {% if info.TF_TIPO_DOCUMENTO == "D" %}
                        	<option value="D" selected="selected">D - Documento Nacional de Identidad (DNI)</option>
                        {% else %}
                        	<option value="D">D - Documento Nacional de Identidad (DNI).</option>
                        {% endif %}
                        {% if info.TF_TIPO_DOCUMENTO == "E" %}
                        	<option value="E" selected="selected">E - Número de Identificador de Extranjero (NIE)</option>
                        {% else %}
                        	<option value="E">E - Número de Identificador de Extranjero (NIE)</option>
                        {% endif %}
                        {% if info.TF_TIPO_DOCUMENTO == "U" %}
                        	<option value="U" selected="selected">U - Identificación convencional para ciudadanos del Espacio Económico Europeo sin NIE</option>
                        {% else %}
                        	<option value="U">U - Identificación convencional para ciudadanos del Espacio Económico Europeo sin NIE</option>
                        {% endif %}
                        {% if info.TF_TIPO_DOCUMENTO == "G" %}
                        	<option value="G" selected="selected">G - Personas privadas de libertad</option>
                        {% else %}
                        	<option value="G">G - Personas privadas de libertad</option>
                        {% endif %}
                        {% if info.TF_TIPO_DOCUMENTO == "W" %}
                        	<option value="W" selected="selected">W - Identificación convencional para ciudadanos que no pertenecen Espacio Económico Europeo y sin NIE</option>
                        {% else %}
                        	<option value="W">W - Identificación convencional para ciudadanos que no pertenecen Espacio Económico Europeo y sin NIE</option>
                        {% endif %}
                        {% if info.TF_TIPO_DOCUMENTOO == "H" %}
                        	<option value="H" selected="selected">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% else %}
                        	<option value="H">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% endif %}
                        </select>
                        	</div>
                        </div>
                       
                        <div class="form-group">
                            <label class="control-label col-sm-3">Número de documento: </label>
                            <div class="col-sm-3">
                                <input class="form-control" type="text" name="TF_NUM_DOCUMENTO" value="" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-sm-3">Letra NIF: </label>
                            <div class="col-sm-2">
                                <input class="form-control" type="text" name="TF_LETRA_NIF" value="" />
                            </div>
                        </div>
                    	
                        <div class="alert alert-warning">
                        El campo de "Número del documento" tiene una longitud de 10 caracteres alfanuméricos.
                        <table id="tabla_info_nif">
                        <tr><th>Tipo</th><th>Número</th><th>Carácter de control NIF</th></tr>
                        <tr><td>D</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>E</td><td>bbXN7<br />bbYN7<br />bbZN7</td><td>L<br />L<br />L</td></tr>
                        <tr><td>U</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>W</td><td>bbN8</td><td>L</td></tr>
                        <tr><td>G</td><td>N10</td><td>L</td></tr>
                        <tr><td>H</td><td>bbN8</td><td>L</td></tr>
                        </table>
                        </div>
                    </div>
                    </div>
                </div>    
                
              
               <div class="well subcampo">
                {% if new_participant == "SI" %}
					<legend>ESPECIALIDADES DEL PARTICIPANTE: </legend>
                	<div class="alert alert-warning">Debe guardar los cambios antes de crear una especialidad al participante.</div>
                {% else %}
                	<legend>ESPECIALIDADES DEL PARTICIPANTE: 
                	<a href="editar-especialidad-participante.php?new_specialty=SI&cod_participant={{ info.cod }}&cod_action={{ cod_action }}" class="btn btn-sm btn-info pull-right">Crear especialidad</a>
                	</legend>
                   {% for specialty in listParticipantSpecialty %}
                	<div class="form-group">
                        <label class="control-label col-sm-3">Especialidad: </label>
                        <div class="col-sm-9">
                            <label class="campo_texto">{{ specialty.ORIGEN_ESPECIALIDAD }} {{ specialty. 	AREA_PROFESIONAL }} {{ specialty.CODIGO_ESPECIALIDAD }}
                            <a href="#" class="btn btn-danger btn-sm pull-right mlateral del_specialty_participant" id="specialty{{ specialty.cod }}">Borrar</a>
                            <a href="editar-especialidad-participante.php?new_specialty=NO&cod_participant={{ info.cod }}&cod_specialty={{ specialty.cod }}&cod_action={{ cod_action }}" class="btn btn-warning btn-sm pull-right mlateral">Editar</a>
                            </label>
                        </div>
                    </div>
                {% endfor %}
                     
                {% endif %}
                </div>
                
                </fieldset>
        </div>
    </div>
    </form>
</div>
