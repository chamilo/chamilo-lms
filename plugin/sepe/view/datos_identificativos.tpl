<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
                <li class="nav-header"><h3>Opciones:</h3></li>
                <li class="sepe_editar_link">
                    <a href="editar-datos-identificativos.php">
                    {% if info == false %}
                    	Nuevo centro
                    {% else %}
                        Editar centro
                    {% endif %}
                    </a>
                </li>
                <li class="sepe_borrar_link">
                	<a href="borrar-datos-identificativos.php" id="borrar_datos_identificativos">Borrar centro</a>
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
                <legend>Datos Identificativos del Centro</legend>
                {% if info != false %}
            	<div class="form-group ">
                    <label class="col-sm-3 control-label">Origen Centro</label>
                    <div class="col-sm-9">
                    	<label class="campo_texto text-primary">{{ info.origen_centro }}</label>
                    </div>
                </div>

                <div class="form-group ">
                    <label class="col-sm-3 control-label">Código Centro</label>
                    <div class="col-sm-9">
                    	<label class="campo_texto text-primary">{{ info.codigo_centro }}</label>
                    </div>
                </div>

                <div class="form-group ">
                    <label class="col-sm-3 control-label">Nombre Centro</label>
                    <div class="col-sm-9">
                    	<label class="campo_texto text-primary">{{ info.nombre_centro }}</label>
                    </div>
                </div>

                <div class="form-group ">
                    <label class="col-sm-3 control-label">URL plataforma</label>
                    <div class="col-sm-9">
                    	<label class="campo_texto text-primary">{{ info.url }}</label>
                    </div>
                </div>

                <div class="form-group ">
                    <label class="col-sm-3 control-label">URL seguimiento</label>
                    <div class="col-sm-9">
                    	<label class="campo_texto text-primary">{{ info.url_seguimiento }}</label>
                    </div>
                </div>

                <div class="form-group ">
                    <label class="col-sm-3 control-label">Teléfono</label>
                    <div class="col-sm-9">
                    	<label class="campo_texto text-primary">{{ info.telefono }}</label>
                    </div>
                </div>

                <div class="form-group ">
                    <label class="col-sm-3 control-label">E-mail</label>
                    <div class="col-sm-9">
                    	<label class="campo_texto text-primary">{{ info.email }}</label>
                    </div>
                </div>
                {% else %}
                	<div class="alert alert-danger">No hay datos identificativos del centro</div>
                {% endif %}
                </fieldset>
            </form>
        </div>
    </div>
</div>
