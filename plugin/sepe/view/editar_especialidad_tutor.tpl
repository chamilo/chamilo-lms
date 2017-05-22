<script type='text/javascript' src="../js/sepe.js"></script>
<script type='text/javascript'>
	$(document).ready(function () {
		//Al pulsar submit se comprueba si al guardar una edición un profesor chamilo existente puede
		// remplazar sus datos
		$("input[type='submit']").click(function(e){
			e.preventDefault();
			e.stopPropagation();
			if( $("#slt_user_existente").val() == "SI" ){
				if($("select[name='tutor_existente']").val()==""){
					alert("Seleccione un tutor de la lista o seleccione Crear nuevo tutor")
				}else{
					$("form").submit();		
				}
			}else{
				var tipo_documento = $("select[name='TIPO_DOCUMENTO']").val();
				var num_documento = $("input[name='NUM_DOCUMENTO']").val();
				var letra_nif = $("input[name='LETRA_NIF']").val();
				vcodchamilo = $("select[name='cod_user_chamilo']").val();
				if($.trim(tipo_documento)=='' || $.trim(num_documento)=='' || $.trim(letra_nif)==''){
					alert("Los campos de Identificador del tutor son obligatorios");
				}else{
					if($("input[name='new_tutor']" ).val()=="NO"){
						$.post("function.php", {tab:"comprobar_editar_tutor", tipo:tipo_documento, num:num_documento, letra:letra_nif, codchamilo:vcodchamilo},
						function (data) {
							if (data.status == "false") {
								if(confirm(data.content)){
									$("form").submit();
								}
							} else {
								$("form").submit();
							}
						}, "json");
					}else{
						$("form").submit();	
					}
				}
			}
		});
	});
</script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
	<form class="form-horizontal" action="editar-especialidad-tutor.php" method="post" name="form_specialty_action">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Acciones:</h3></li>
                <li>
                {% if new_tutor == "SI" %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="new_tutor" value="SI" />
                {% else %}
                	<input type="hidden" name="cod_action" value="{{ cod_action }}" />
                    <input type="hidden" name="cod_specialty" value="{{ cod_specialty }}" />
                    <input type="hidden" name="cod_s_tutor" value="{{ cod_tutor }}" />
                    <input type="hidden" name="new_tutor" value="NO" />
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
        
        {% if new_tutor == "SI" %}
        <div class="well_border">
        <div class="form-group">
            <label class="control-label col-sm-3">Usar tutor existente: </label>
            <div class="col-sm-9">
            <select  id="slt_user_existente" class="form-control" name="slt_user_existente">
                <option value="SI" selected="selected">Usar existente</option>
                <option value="NO">Crear nuevo tutor</option>
            </select>
            </div>
        </div>
        </div>
        
        <div class="well_border" id="box_listado_tutores">
        	<fieldset>
            <legend>Listado de tutores</legend>
            	<div class="form-group">
                    <label class="control-label col-sm-3">Tutor: </label>
                    <div class="col-sm-9">
                    <select  name="tutor_existente" class="form-control">
                    <option value=""></option>
                    {% for tutor in listTutorsExistentes %}
                    <option value="{{ tutor.cod }}">{{ tutor.datos }}</option>
                    {% endfor %}
                    
                    </select>
                           
                        </div>
                    </div>
            </fieldset>
        </div>
        <div class="well_border" style="display:none" id="box_datos_tutor">
        
        {% else %}
          <input type="hidden" name="slt_user_existente" value="NO" />
          <div class="well_border" id="box_datos_tutor">
        {% endif %}
            	<fieldset>
                <legend>Tutor - Formador</legend>
                <div class="well subcampo">
				<legend class="subcampo">IDENTIFICADOR DEL TUTOR: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Tipo del documento: </label>
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
                        {% if info.TIPO_DOCUMENTO == "H" %}
                        	<option value="H" selected="selected">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% else %}
                        	<option value="H">H - Identificación convencional de Personas que no hayan podido ser adecuadas en el proceso de adecuación de datos</option>
                        {% endif %}
                        </select>
                           
                        </div>
                    </div>

                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Número del documento: </label>
                        <div class="col-sm-2">
                            <input class="form-control" type="text" name="NUM_DOCUMENTO" value="{{ info.NUM_DOCUMENTO }}" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-sm-3">Letra del NIF: </label>
                        <div class="col-sm-1">
                            <input class="form-control" type="text" name="LETRA_NIF" value="{{ info.LETRA_NIF }}" />
                        </div>
                    </div>
                    
                    <div class="warning-message">
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
                    <label class="control-label col-sm-3">Acreditación del tutor: </label>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="ACREDITACION_TUTOR" value="{{ info.ACREDITACION_TUTOR }}" style="width:100%" />
                        <div class="alert alert-info mensaje_info mtop5">Titulación o certificación de la formación académica o profesional que posee.</div>
                    </div>
                </div>
                    
                <div class="form-group">
                    <label class="control-label col-sm-3">Experiencia profesional: </label>
                    <div class="col-sm-2">
                        <input class="form-control" class="numerico" type="number" name="EXPERIENCIA_PROFESIONAL" value="{{ info.EXPERIENCIA_PROFESIONAL }}" />
                    </div>
                    <div class="alert alert-info mensaje_info col-sm-7">Duración (en años) de experiencia profesional en el campo de las competencias relacionadas con el módulo formativo.</div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Competencia docente: </label>
                    <div class="col-sm-9">
                    	<select  name="COMPETENCIA_DOCENTE" class="form-control" >
                        <option value=""></option>
                        {% if info.COMPETENCIA_DOCENTE == "01" %}
                        	<option value="01" selected="selected">Certificado de profesionalidad de docencia de la formación profesional para el empleo</option>
                        {% else %}
                        	<option value="01">Certificado de profesionalidad de docencia de la formación profesional para el empleo</option>
                        {% endif %}
                        {% if info.COMPETENCIA_DOCENTE == "02" %}
                        	<option value="02" selected="selected">Certificado de profesionalidad de formador ocupacional</option>
                        {% else %}
                        	<option value="02">Certificado de profesionalidad de formador ocupacional</option>
                        {% endif %}{% if info.COMPETENCIA_DOCENTE == "03" %}
                        	<option value="03" selected="selected">Certificado de Aptitud Pedagógica o título profesional de Especialización Didáctica o Certificado de Cualificación Pedagógica</option>
                        {% else %}
                        	<option value="03">Certificado de Aptitud Pedagógica o título profesional de Especialización Didáctica o Certificado de Cualificación Pedagógica</option>
                        {% endif %}{% if info.COMPETENCIA_DOCENTE == "04" %}
                        	<option value="04" selected="selected">Máster Universitario</option>
                        {% else %}
                        	<option value="04">Máster Universitario</option>
                        {% endif %}{% if info.COMPETENCIA_DOCENTE == "05" %}
                        	<option value="05" selected="selected">Curso de formación equivalente a la formación pedagógica y didáctica</option>
                        {% else %}
                        	<option value="05">Curso de formación equivalente a la formación pedagógica y didáctica</option>
                        {% endif %}{% if info.COMPETENCIA_DOCENTE == "06" %}
                        	<option value="06" selected="selected">Experiencia docente contrastada de al menos 600 horas de impartición de acciones formativas</option>
                        {% else %}
                        	<option value="06">Experiencia docente contrastada de al menos 600 horas de impartición de acciones formativas</option>
                        {% endif %}
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-sm-3">Experiencia modalidad teleformación: </label>
                    <div class="col-sm-2">
                        <input class="form-control" type="number"  name="EXPERIENCIA_MODALIDAD_TELEFORMACION" value="{{ info.EXPERIENCIA_MODALIDAD_TELEFORMACION }}" />
                    </div>
                    <div class="col-sm-7 alert alert-info mensaje_info">Número entero que equivale a la duración (en horas) de experiencia docente en modalidad de teleformación.</div>
                </div>
                    
                <div class="form-group">
                    <label class="control-label col-sm-3">Formación modalidad teleformación: </label>
                    <div class="col-sm-9">
                    <select  name="FORMACION_MODALIDAD_TELEFORMACION" class="form-control">
                        <option value=""></option>
                        {% if info.FORMACION_MODALIDAD_TELEFORMACION == "01" %}
                        	<option value="01" selected="selected">Certificado de profesionalidad de docencia de la formación profesional para el empleo</option>
                        {% else %}
                        	<option value="01">Certificado de profesionalidad de docencia de la formación profesional para el empleo</option>
                        {% endif %}
                        {% if info.FORMACION_MODALIDAD_TELEFORMACION == "02" %}
                        	<option value="02" selected="selected">Acreditación parcial acumulable correspondiente al módulo formativo MF1444_3</option>
                        {% else %}
                        	<option value="02">Acreditación parcial acumulable correspondiente al módulo formativo MF1444_3</option>
                        {% endif %}
                        {% if info.FORMACION_MODALIDAD_TELEFORMACION == "03" %}
                        	<option value="03" selected="selected">Diploma expedido por la administración laboral competente que certifique que se ha superado con evaluación positiva la formación, de duración no inferior a 30 horas</option>
                        {% else %}
                        	<option value="03">Diploma expedido por la administración laboral competente que certifique que se ha superado con evaluación positiva la formación, de duración no inferior a 30 horas</option>
                        {% endif %}
                        {% if info.FORMACION_MODALIDAD_TELEFORMACION == "04" %}
                        	<option value="04" selected="selected">Diploma que certifique que se han superado con evaluación positiva acciones de formación, de al menos 30 horas de duración</option>
                        {% else %}
                        	<option value="04">Diploma que certifique que se han superado con evaluación positiva acciones de formación, de al menos 30 horas de duración</option>
                        {% endif %}
                    </select>
                	</div>
                </div>
                
                <div class="well subcampo">
				<legend class="subcampo">PROFESOR CURSO CHAMILO: </legend>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Profesor: </label>
                        <div class="col-sm-9">
                        <select  name="cod_user_chamilo" class="form-control">
                        <option value=""></option>
                        {% for profesor in listProfesor %}
                        {% if info.cod_user_chamilo == profesor.user_id %}
                        	<option value="{{ profesor.user_id }}" selected="selected">{{ profesor.firstname }} {{ profesor.lastname }}</option>
                        {% else %}
                        	<option value="{{ profesor.user_id }}">{{ profesor.firstname }} {{ profesor.lastname }}</option>
                        {% endif %}
                        {% endfor %}
                        </select>
            	  </div>
              </div>
         </div>
    </div>
    </form>
</div>
