<script type='text/javascript' src="../js/funciones.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <table id="tabla_cursos" class="data_table">
            <tr class="row_odd">
                <th>{{ 'Title'|get_lang }}</th>
                <th>{{ 'OfficialCode'|get_lang }}</th>
                <th class="ta-center">{{ 'Visible'|get_lang }}</th>
                <th class="span2">{{ 'Price'|get_lang }}</th>
                <th class="span1 ta-center">{{ 'Options'|get_lang }}</th>
            </tr>
            {% set i = 0 %}

            {% for curso in cursos %}
            {{ i%2==0 ? '
            <tr class="row_even">' : '
            <tr class="row_odd">' }}
                {% set i = i + 1 %}
                <td>
                {{ visibilidad[curso.visibility] }}
                <a href="{{ server }}courses/{{curso.code}}/index.php">{{curso.title}}</a>
                <span class="label label-info">{{ curso.visual_code }}</span>
        </td>
        <td>{{curso.code}}</td>
                <td class="ta-center">
                {% if curso.visible=="SI" %}
                <input type="checkbox" name="visible" value="SI" checked="checked" size="6" />
                {% else %}
                <input type="checkbox" name="visible" value="SI" size="6" />
                {% endif %}
                </td>
                <td><input type="text" name="price" value="{{curso.price}}" class="span1 price" /> {{ moneda }}</td>
        <td class=" ta-center" id="curso{{ curso.id }}">
                <div class="confirmado"><img src="{{ ruta_imagen_ok }}" alt="ok"/></div>
                <div class="modificado" style="display:none"><img src="{{ ruta_imagen_save }}" alt="guardar"
                                                                  class="cursor guardar"/></div>
                </td>
            </tr>
{% endfor %}

</table>
</div>
<div class="cleared"></div>
</div>