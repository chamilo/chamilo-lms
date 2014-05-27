<script type='text/javascript' src="../js/funciones.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <div id="course_category_well" class="well span3">
            <ul class="nav nav-list">
                <li class="nav-header"><h4>Datos del Usuario:</h4></li>
                <li class="nav-header">Nombre:</li>
                <li><h5>{{ name | e }}</h5></li>
                <li class="nav-header">Usuario</li>
                <li><h5>{{ user | e }}</h5></li>
                <li class="nav-header">E-mail de notificaciones:</li>
                <li><h5>{{ email | e}}</h5></li>
                <br/>
            </ul>
        </div>

        <br/><br/>

        <div class="well_border span8">
            <div class="row">
                <div class="span">
                    <div class="thumbnail">
                        <a class="ajax" rel="gb_page_center[778]" title=""
                           href="{{ server }}plugin/buy_courses/src/ajax.php?code={{ course.code }}">
                            <img src="{{ server }}{{ course.course_img }}">
                        </a>
                    </div>
                </div>
                <div class="span4">
                    <div class="categories-course-description">
                        <h3>{{ course.title }}</h3>
                        <h5>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</h5>
                    </div>
                </div>
                <div class="span right">
                    <div class="sprice right">{{ course.price }} {{ currency }}</div>
                    <div class="cleared"></div>
                    <div class="btn-toolbar right">
                        <a class="ajax btn btn-primary" title=""
                           href="{{ server }}plugin/buy_courses/src/ajax.php?code={{ course.code }}">{{
                            'Description'|get_lang }}</a>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cleared"></div>
    <hr/>
    <div align="center">
        <table class="data_table" style="width:70%">
            <tr>
                <th class="ta-center">Datos Bancarios</th>
            </tr>
            {% set i = 0 %}
            {% for account in accounts %}
            {{ i%2==0 ? '
            <tr class="row_even">' : '
            <tr class="row_odd">' }}
                {% set i = i + 1 %}
                <td class="ta-center">
                <font color="#0000FF">{{ account.name | e }}</font><br/>
                {% if account.swift != '' %}
                SWIFT: <strong>{{ account.swift | e }}</strong><br/>
                {% endif %}
                Cuenta Bancaria: <strong>{{ account.account | e }}</strong><br/>
                </td></tr>
            {% endfor %}
            </table>
            <br />
            <div class="normal-message">{{ 'Message_conf_transf'|get_lang | e}}
    </div>
    <br/>

    <form method="post" name="Aceptar" action="../src/process_confirm.php">
        <input type="hidden" name="payment_type" value="Transference"/>
        <input type="hidden" name="name" value="{{ name | e }}"/>
        <input type="hidden" name="price" value="{{ course.price }}"/>
        <input type="hidden" name="title" value="{{ course.title | e }}"/>

        <div class="btn_siguiente">
            <input class="btn btn-success" type="submit" name="Aceptar" value="Confirm the order/>
            <input class="btn btn-danger" type="button" name="Cancelar" value="Cancelar" id="CancelOrder"/>
        </div>
    </form>
</div>
<div class="cleared"></div>
</div>