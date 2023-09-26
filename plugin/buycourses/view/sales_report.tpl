<ul class="nav nav-tabs buy-courses-sessions-tabs" role="tablist">
    <li id="buy-courses-sessions-tab" class="active" role="presentation">
        <a href="sales_report.php" aria-controls="buy-courses_sessions"
           role="tab">{{ 'CourseSessionBlock'|get_lang }}</a>
    </li>
    {% if services_are_included %}
        <li id="buy-services-tab" class="{{ showing_services ? 'active' : '' }}" role="presentation">
            <a href="service_sales_report.php" aria-controls="buy-services"
               role="tab">{{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}</a>
        </li>
    {% endif %}
    <li id="buy-subscriptions-tab" role="presentation">
        <a href="subscription_sales_report.php" aria-controls="buy-subscriptions"
           role="tab">{{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}</a>
    </li>
</ul>
<br />
<br />
{{ form }}

<div class=" col-md-12">
    <table class="col-md-12" id="table_report">
    </table>
    <div id="tblGridPager" style="text-align:center" > </div>
</div>

<script>
    $(function () {
        $('[name="filter_type"]').on('change', function () {
            var self = $(this);

            if (self.val() === '0') {
                $('#report-by-user').hide();
                $('#report-by-status').show();
                $('#report-by-date').hide();
                $('#report-by-email').hide();
            } else if (self.val() === '1') {
                $('#report-by-status').hide();
                $('#report-by-user').show();
                $('#report-by-date').hide();
                $('#report-by-email').hide();
            } else if (self.val() === '2') {
                $('#report-by-status').hide();
                $('#report-by-user').hide();
                $('#report-by-date').show();
                $('#report-by-email').hide();
            } else if (self.val() === '3') {
                $('#report-by-status').hide();
                $('#report-by-user').hide();
                $('#report-by-date').hide();
                $('#report-by-email').show();
            }
        });
    });
</script>
