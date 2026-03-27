{% autoescape false %}
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
<div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-25">
        <thead>
        <tr>
            <th class="text-center"><input type="checkbox" id="checkAll"></th>
            <th class="text-center">{{ 'OrderReference'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'OrderDate'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-right">{{ 'Commission'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-right">{{ 'PayPalAccount'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-right">{{ 'Options'| get_lang }}</th>
        </tr>
        </thead>
        <tbody>
        {% for payout in payout_list %}
            <tr style="{{ payout.paypal_account ? '' : 'color: red;' }}">
                <td class="text-center">{% if payout.paypal_account %} <input
                            id="{{ payout.id }}" type="checkbox" name="data[]"
                            value="{{ payout.commission }}"> {% endif %}</td>
                <td class="text-center">{{ payout.reference }}</td>
                <td class="text-center">{{ payout.date }}</td>
                <td class="text-right"
                   >{{ payout.currency ~ ' ' ~ payout.commission }}</td>
                {% if payout.paypal_account %}
                    <td class="text-right">{{ payout.paypal_account }}</td>
                {% else %}
                    <td class="text-right"
                       >{{ 'NoPayPalAccountDetected'| get_plugin_lang('BuyCoursesPlugin') }}</td>
                {% endif %}
                <td class="text-right">
                    <button id="{{ payout.id }}" type="button"
                            class="btn btn-danger fa fa-ban cancelPayout"> {{ 'CancelPayout'| get_plugin_lang('BuyCoursesPlugin') }}</button>
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>

    <div id="startPayout" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ 'PaypalPayoutCommissions'|get_plugin_lang('BuyCoursesPlugin') }}</h4>
                </div>
                <div class="modal-body" id="content">

                </div>
                <div class="modal-footer">
                    <button id="proceedPayout" type="button"
                            class="btn btn-success fa fa-paypal"> {{ 'ProceedPayout'|get_plugin_lang('BuyCoursesPlugin') }}</button>
                    <button id="cancelPayout" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2"
                            data-dismiss="modal">{{ 'Cancel'|get_lang }}</button>
                    <button id="responseButton" type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2">{{ 'Confirm'|get_lang }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div>
    <button id="continuePayout" type="button" class="btn btn-primary fa fa-caret-square-o-right" data-toggle="modal"
            data-target="#startPayout" data-backdrop="static"
            data-keyboard="false"> {{ 'ContinuePayout'|get_plugin_lang('BuyCoursesPlugin') }}</button>
</div>

<script>
    $(function () {
        $("#responseButton").hide();
        $("#checkAll").click(function () {
            $(':checkbox').prop('checked', this.checked);
        });

        $('#continuePayout').click(function () {

            var val = [];

            $(':checkbox:checked').not('#checkAll').each(function (i) {
                val[i] = $(this).attr("id");
            });

            $.ajax({
                data: {payouts: val},
                url: '{{ url('index') ~ 'plugin/BuyCourses/src/buycourses.ajax.php?' ~  { 'a': 'processPayout' }|url_encode }}',
                type: 'POST',
                success: function (response) {
                    $("#content").html(response);
                    (jQuery.isEmptyObject(val)) ? $('#proceedPayout').prop("disabled", true) : $('#proceedPayout').prop("disabled", false);
                }
            });
        });

        $('#proceedPayout').click(function () {

            var val = [];

            $(':checkbox:checked').not('#checkAll').each(function (i) {
                val[i] = $(this).attr("id");
            });

            $.ajax({
                data: {payouts: val},
                url: '{{ url('index') ~ 'plugin/BuyCourses/src/buycourses.ajax.php?' ~  { 'a': 'proceedPayout' }|url_encode() }}',
                type: 'POST',
                beforeSend: function () {
                    $("#proceedPayout").hide();
                    $("#cancelPayout").hide();
                    $("#spinner").html('<br /><br /><div class="wobblebar-loader"></div><p> {{ 'ProcessingPayoutsDontCloseThisWindow'|get_plugin_lang('BuyCoursesPlugin') }} </p>');
                },
                success: function (response) {
                    $("#content").html(response);
                    $("#responseButton").show();
                }
            });
        });

        $(".cancelPayout").click(function () {
            var id = this.id;
            $.ajax({
                data: 'id=' + id,
                url: '{{ url('index') ~ 'plugin/BuyCourses/src/buycourses.ajax.php?' ~  { 'a': 'cancelPayout' }|url_encode }}',
                type: 'POST',
                success: function () {
                    window.location.reload();
                }
            });
        });

        $('#responseButton').click(function () {
            window.location.reload();
        });
    });
</script>
</div>
{% endautoescape %}
