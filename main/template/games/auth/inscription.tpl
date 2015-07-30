{%
    extends hide_header == true
    ? template ~ "/layout/blank.tpl"
    : template ~ "/layout/layout_1_col.tpl"
%}

{% block content %}

{{ inscription_content }}
{% if text_after_registration is empty %}
    <form class="registration-form" action="/main/auth/inscription.php" method="post" name="registration" id="registration">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nombre</label>
                    <input size="40" class="form-control" name="firstname" type="text">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input size="40" class="form-control" name="email" type="text">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input id="pass1" size="20" autocomplete="off" class="form-control" name="pass1" type="password">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Apellidos</label>
                    <input size="40" class="form-control" name="lastname" type="text">
                </div>


                <div class="form-group ">
                    <label> Fecha de nacimiento </label>
                        <script type="text/javascript">
                            $(function() {
                                $('#extra_date_of_birth').datepicker({
                                    dateFormat: 'yy-mm-dd'
                                });
                            });
                        </script>
                    <input id="extra_date_of_birth" class="form-control" name="extra_date_of_birth" type="text">
                </div>


                <!-- <div class="form-group">
                    <label>Nombre de usuario</label>
                    <input id="username" size="40" class="form-control" name="username" type="text">
                </div> -->
                <div class="form-group">
                    <label>Confirme contraseña</label>
                    <input id="pass2" size="20" autocomplete="off" class="form-control" name="pass2" type="password">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <input name="_qf__registration" value="" type="hidden">
                <button class="btn btn-press" name="submit" type="submit">Regístrate</button>
                <div class="terms">
                    Al crear una cuenta, aceptas las Condiciones del servicio y la Política de privacidad de Tademi
                </div>
                <hr class="separator"></hr>
            </div>
        </div>
    </form>
{% endif %}
{{ text_after_registration }}

{% endblock %}
