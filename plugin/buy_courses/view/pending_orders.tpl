<script type='text/javascript' src="../js/funciones.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <table id="tabla_pedidos" class="data_table">
            <tr class="row_odd">
                <th class="ta-center">{{ 'Ref_pedido'|get_lang }}</th>
                <th>{{ 'Name'|get_lang }}</th>
                <th>{{ 'Title'|get_lang }}</th>
                <th class="span2">{{ 'Price'|get_lang }}</th>
                <th class="ta-center">{{ 'Date'|get_lang }}</th>
                <th class="span2 ta-center">{{ 'Options'|get_lang }}</th>
            </tr>
            {% set i = 0 %}

            {% for pedido in pendientes %}
            {{ i%2==0 ? '
            <tr class="row_even">' : '
            <tr class="row_odd">' }}
                {% set i = i + 1 %}
                <td class="ta-center">{{ pedido.reference }}</td>
                <td>{{ pedido.name }}</td>
                <td>{{ pedido.title }}</td>
                <td>{{ pedido.price }} {{ currency }}</td>
                <td class="ta-center">{{ pedido.date }}</td>
                <td class="ta-center" id="pedido{{ pedido.cod }}">
                    <img src="{{ confirmation_img }}" alt="ok" class="cursor confirm_order"
                         title="Subscribir al usuario"/>
                    &nbsp;&nbsp;
                    <img src="{{ ruta_imagen_borrar }}" alt="borrar" class="cursor clear_order"
                         title="Eliminar el pedido"/>
                </td>
            </tr>
{% endfor %}

</table>
</div>
<div class="cleared"></div>
</div>