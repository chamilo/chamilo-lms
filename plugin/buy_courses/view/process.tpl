<script type='text/javascript' src="../js/funciones.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <div id="course_category_well" class="well span3">
            <ul class="nav nav-list">
                <li class="nav-header"><h4>Datos del Usuario:</h4></li>
                <li class="nav-header">Nombre:</li>
                <li><h5>{{ name }}</h5></li>
                <li class="nav-header">Usuario</li>
                <li><h5>{{ user }}</h5></li>
                <li class="nav-header">E-mail de notificaciones:</li>
                <li><h5>{{ email }}</h5></li>
                <br/>
            </ul>
        </div>

        <br/><br/>

        <div class="well_border span8">
            <div class="row">
                <div class="span">
                    <div class="thumbnail">
                        <a class="ajax" rel="gb_page_center[778]" title=""
                           href="{{ server }}plugin/buy_courses/function/ajax.php?code={{ curso.code }}">
                            <img alt="" src="{{ server }}{{ curso.course_img }}">
                        </a>
                    </div>
                </div>
                <div class="span4">
                    <div class="categories-course-description">
                        <h3>{{ curso.title }}</h3>
                        <h5>{{ 'Teacher'|get_lang }}: {{ curso.teacher }}</h5>
                    </div>
                </div>
                <div class="span right">
                    <div class="sprice right">{{ curso.price }} {{ currency }}</div>
                    <div class="cleared"></div>
                    <div class="btn-toolbar right">
                        <a class="ajax btn btn-primary" title=""
                           href="{{ server }}plugin/buy_courses/function/ajax.php?code={{ curso.code }}">{{'Description'|get_lang }}
                        </a>
                    </div>
                </div>
            </div>
        </div>


    </div>
    <div class="cleared"></div>
    <hr/>
    <div align="center">
        <form action="../src/process_confirm.php" method="post">
            <table>
                <tr>
                    <th>
                        <legend><h3> M&eacute;todos de Pago:</h3></legend>
                    </th>
                </tr>
                {% if paypal_enable == "true" %}
                <tr><td><input type="radio" id="payment_type-p" name="payment_type" value="PayPal" /> {{ 'paypal'|get_lang
                }}</td></tr>
                {% endif %}

                {% if transference_enable == "true" %}
                <tr><td><input type="radio" id="payment_type-tra" name="payment_type" value="Transferencia" />{{
                'transferencia_bancaria'|get_lang }}</td></tr>
                {% endif %}
                <tr><td>
                <input type="hidden" name="currency_type" value="{{ currency }}" />
                <input type="hidden" name="server" value="{{ server }}"/>
                <input type="submit" class="btn btn-success" value="{{ 'confirmar_compra'|get_lang }}"/>
                </td></tr>
            </table>
        </form>
    </div>
    <div class="cleared"></div>
</div>