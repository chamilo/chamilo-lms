{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}

<form id="lostpassword" class="form-horizontal" action="/main/auth/lostPassword.php" method="post" name="lost_password" id="lost_password">
        <div class="page-header">
            <h4 class="title-section">¿Ha olvidado su contraseña?</h4>
        </div>

        <div class="form-group ">
            <label class="col-sm-3 control-label">
                <span class="form_required">*</span>
                Nombre de usuario o dirección e-mail
            </label>
            <div class="col-sm-7">
                <input size="40" class="form-control" name="user" type="text">
                <p class="help alert alert-warning">Escriba el nombre de usuario o la dirección de correo electrónico con la que está registrado y le remitiremos su contraseña.</p>
            </div>
            <div class="col-sm-2"></div>
        </div>
        <div class="form-group ">
            <label class="col-sm-3 control-label">

            </label>
            <div class="col-sm-7">
                <button class="btn btn-press" name="submit" type="submit"><i class="fa fa-paper-plane"></i> Reenviar contraseña</button></div>
            <div class="col-sm-2"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-12"><span class="form_required">*</span> <small>Contenido obligatorio</small></div>
        </div>
        <div class="clear"></div>

    <input name="_qf__lost_password" value="" type="hidden">

</form>

{% endblock %}
