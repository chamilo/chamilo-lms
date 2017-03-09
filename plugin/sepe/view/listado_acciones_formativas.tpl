<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-12">
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
        <div class="page-header">
       		<h2>Listado de acciones formativas</h2>
        </div>
        
        <div class="report_section">
			{% if lista_curso_acciones|length > 0 %}
            <table class="table table-bordered box_centrado" style="width:auto"> 
        	{% for lista in lista_curso_acciones %}
            	<tr>
                    <td class="va_middle">Curso: <strong>{{ lista.title }}</strong> -> ID ACCION: <strong>{{ lista.ORIGEN_ACCION }} {{ lista.CODIGO_ACCION }}</strong></td>
                    <td class="ta-center">
                        <a href="#" class="btn btn-danger btn-sm mlateral del_action_formativa" id="cod{{ lista.cod_action }}">Borrar</a>
                        <a href="#" class="btn btn-warning btn-sm mlateral desvincular_accion" id="cod{{ lista.cod }}">Desvincular</a>
                        <a href="accion-formativa.php?cid={{ lista.id_course }}" class="btn btn-info btn-sm mlateral">Ver / Editar</a>
                        
                    </td>
               	</tr>
            {% endfor %} 
            </table>
            {% else %}
            	<div class="alert alert-warning">
                    No hay acciones formativas asociadas a un curso.
                </div>
            {% endif %}
        </div>
        
        <hr />
        
        <div class="page-header">
       		<h2>Cursos sin acciones formativas asignadas</h2>
        </div>

        <div class="report_section">
			<table class="table table-striped"> 
        	{% for lista in lista_curso_libre_acciones %}
            	<tr>
                    <td class="va_middle">Curso: <strong>{{ lista.title }}</strong></td>
                    <td class="ta-center va_middle">
                    	<select class="chzn-select" id="accion_formativa{{ lista.id }}" style="width:250px">
                        	<option value="">Seleccione una acción formativa</option>
                            {% for accion in lista_acciones_libres %}
                            	<option value="{{ accion.cod }}">
                                    {{ accion.ORIGEN_ACCION }} {{ accion.CODIGO_ACCION }}
                                </option>
                            {% endfor %}  
                        </select>
                    </td>
                    <td class="ta-center va_middle" style="min-width:240px">
                        <a href="#" class="btn btn-info btn-sm mlateral asignar_action_formativa" id="course_code{{ lista.id }}">Asignar acción</a>
                        <a href="editar-accion-formativa.php?new_action=SI&cid={{ lista.id }}" class="btn btn-success btn-sm mlateral">Crear acción</a>
                    </td>
               	</tr>
            {% endfor %} 
            </table>
        </div> 
    </div>
</div>
