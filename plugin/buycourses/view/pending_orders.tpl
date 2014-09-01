<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <table id="orders_table" class="data_table">
            <tr class="row_odd">
                <th class="ta-center">{{ 'ReferenceOrder'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'Name'|get_lang }}</th>
                <th>{{ 'Title'|get_lang }}</th>
                <th class="span2">{{ 'Price'|get_lang }}</th>
                <th class="ta-center">{{ 'Date'|get_lang }}</th>
                <th class="span2 ta-center">{{ 'Options'|get_lang }}</th>
            </tr>
            {% set i = 0 %}

            {% for order in pending %}
                {{ i%2==0 ? '<tr class="row_even">' : '<tr class="row_odd">' }}
                {% set i = i + 1 %}
                <td class="ta-center">{{ order.reference }}</td>
                <td>{{ order.name }}</td>
                <td>{{ order.title }}</td>
                <td>{{ order.price }} {{ currency }}</td>
                <td class="ta-center">{{ order.date }}</td>
                <td class="ta-center" id="order{{ order.cod }}">
                    <img src="{{ confirmation_img }}" alt="ok" class="cursor confirm_order"
                         title="{{ 'SubscribeUser'|get_plugin_lang('BuyCoursesPlugin') }}"/>
                    &nbsp;&nbsp;
                    <img src="{{ delete_img }}" alt="delete" class="cursor clear_order"
                         title="{{ 'DeleteTheOrder'|get_plugin_lang('BuyCoursesPlugin') }}"/>
                </td>
            </tr>
{% endfor %}

</table>
</div>
<div class="cleared"></div>
</div>
