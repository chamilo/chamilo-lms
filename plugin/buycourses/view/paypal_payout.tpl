<div class="table-responsive">
    <table class="table table-striped table-hover">
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
                <td class="text-center" style="vertical-align:middle">{% if payout.paypal_account %} <input
                            id="{{ payout.id }}" type="checkbox" name="data[]"
                            value="{{ payout.commission }}"> {% endif %}</td>
                <td class="text-center" style="vertical-align:middle">{{ payout.reference }}</td>
                <td class="text-center" style="vertical-align:middle">{{ payout.date }}</td>
                <td class="text-right"
                    style="vertical-align:middle">{{ payout.currency ~ ' ' ~ payout.commission }}</td>
                {% if payout.paypal_account %}
                    <td class="text-right" style="vertical-align:middle">{{ payout.paypal_account }}</td>
                {% else %}
                    <td class="text-right"
                        style="vertical-align:middle">{{ 'NoPayPalAccountDetected'| get_plugin_lang('BuyCoursesPlugin') }}</td>
                {% endif %}
                <td class="text-right" style="vertical-align:middle">
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
                    <button id="cancelPayout" type="button" class="btn btn-danger"
                            data-dismiss="modal">{{ 'Cancel'|get_lang }}</button>
                    <button id="responseButton" type="button"
                            class="btn btn-primary">{{ 'Confirm'|get_lang }}</button>
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
                url: '{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?' ~  { 'a': 'processPayout' }|url_encode() }}',
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
                url: '{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?' ~  { 'a': 'proceedPayout' }|url_encode() }}',
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
                url: '{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?' ~  { 'a': 'cancelPayout' }|url_encode() }}',
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