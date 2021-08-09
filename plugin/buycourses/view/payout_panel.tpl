<div id="buy-courses-tabs">

    <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
        <li id="buy-courses-tab" class="" role="presentation">
            <a href="course_panel.php" aria-controls="buy-courses" role="tab">{{ 'MyCourses'| get_lang }}</a>
        </li>
        {% if sessions_are_included %}
            <li id="buy-sessions-tab" class="" role="presentation">
                <a href="session_panel.php" aria-controls="buy-sessions" role="tab">{{ 'MySessions'| get_lang }}</a>
            </li>
        {% endif %}
        <li id="buy-courses-tab" class="active" role="presentation">
            <a href="payout_panel.php" aria-controls="buy-courses"
               role="tab">{{ 'MyPayouts'| get_plugin_lang('BuyCoursesPlugin') }}</a>
        </li>
    </ul>
    {% if not payout_list %}
        <p class="alert alert-info">
            {{ 'WantToSellCourses'|get_plugin_lang('BuyCoursesPlugin') }}
            <a href="#">{{ 'ClickHere'|get_plugin_lang('BuyCoursesPlugin') }}</a>
        </p>
    {% endif %}

    {% if payout_list %}
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th class="text-center">{{ 'OrderReference'| get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'PayoutDate'| get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-right">{{ 'Commission'| get_plugin_lang('BuyCoursesPlugin') }}</th>
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
                    <td class="text-right" style="vertical-align:middle">{{ payout.paypal_account }}</td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    {% endif %}

</div>
