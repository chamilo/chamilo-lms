{{ form }}
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th class="text-center">{{ 'OrderReference'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'PayoutDate'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-right">{{ 'Commission'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-right">{{ 'Names'| get_lang }}</th>
            <th class="text-right">{{ 'PayPalAccount'| get_plugin_lang('BuyCoursesPlugin') }}</th>
        </tr>
        </thead>
        <tbody>
        {% for payout in payout_list %}
            <tr>
                <td class="text-center" style="vertical-align:middle"><a id="{{ payout.sale_id }}" class="saleInfo"
                                                                         data-toggle="modal" data-target="#saleInfo"
                                                                         href="#">{{ payout.reference }}</a></td>
                <td class="text-center" style="vertical-align:middle">{{ payout.payout_date }}</td>
                <td class="text-right"
                    style="vertical-align:middle">{{ payout.currency ~ ' ' ~ payout.commission }}</td>
                <td class="text-right" style="vertical-align:middle">{{ payout.beneficiary }}</td>
                {% if payout.paypal_account %}
                    <td class="text-right" style="vertical-align:middle">{{ payout.paypal_account }}</td>
                {% else %}
                    <td class="text-right"
                        style="vertical-align:middle">{{ 'NoPayPalAccountDetected'| get_plugin_lang('BuyCoursesPlugin') }}</td>
                {% endif %}
            </tr>
        {% endfor %}
        </tbody>
    </table>
    <div id="saleInfo" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ 'SaleInfo'| get_plugin_lang('BuyCoursesPlugin') }}</h4>
                </div>
                <div class="modal-body" id="contentSale">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ 'Close'|get_lang }}</button>
                </div>
            </div>
        </div>
    </div>

    <div id="reportStats" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ 'Stats'| get_plugin_lang('BuyCoursesPlugin') }}</h4>
                </div>
                <div class="modal-body" id="contentStats">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ 'Close'|get_lang }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div>
    <button id="stats" type="button" class="btn btn-primary fa fa-line-chart" data-toggle="modal"
            data-target="#reportStats"> {{ 'Stats'|get_plugin_lang('BuyCoursesPlugin') }}
    </button>
</div>

<script>
    $(function () {
        $(".saleInfo").click(function () {
            var id = this.id;
            $.ajax({
                data: 'id=' + id,
                url: '{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?' ~  { 'a': 'saleInfo' }|url_encode() }}',
                type: 'POST',
                success: function (response) {
                    $("#contentSale").html(response);
                }
            });
        });

        $("#stats").click(function () {
            var id = this.id;
            $.ajax({
                data: 'id=' + id,
                url: '{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?' ~  { 'a': 'stats' }|url_encode() }}',
                type: 'POST',
                success: function (response) {
                    $("#contentStats").html(response);
                }
            });
        });

    });
</script>
