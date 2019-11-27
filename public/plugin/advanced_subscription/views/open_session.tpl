<link href="{{ _p.web_plugin }}advanced_subscription/views/css/style.css" rel="stylesheet" type="text/css">

<script>
    $(function () {
        $('#asp-close-window').on('click', function (e) {
            e.preventDefault();

            window.close();
        });

        $('#asp-go-to').on('click', function (e) {
            e.preventDefault();

            window.close();
            window.opener.location.href = '{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}';
        });
    });
</script>

<h2 class="legal-terms-title legal-terms-popup">
    {{ "SubscriptionToOpenSession"|get_plugin_lang('AdvancedSubscriptionPlugin') }}
</h2>

{% if not is_subscribed %}
    <div class="alert alert-warning legal-terms-popup">
        <ul>
            {% for errorMessage in errorMessages %}
                <li>{{ errorMessage }}</li>
            {% endfor %}
        </ul>
    </div>

    <div class="legal-terms-buttons legal-terms-popup">
        <a
            class="btn btn-success btn-advanced-subscription btn-accept"
            href="#" id="asp-close-window">
            <em class="fa fa-check"></em>
            {{ "AcceptInfinitive"|get_plugin_lang('AdvancedSubscriptionPlugin') }}
        </a>
    </div>
{% else %}
    <div class="alert alert-success legal-terms-popup">
        {{ 'SuccessSubscriptionToSessionX'|get_plugin_lang('AdvancedSubscriptionPlugin')|format(session.name) }}
    </div>

    <div class="text-right legal-terms-popup">
        <a
            class="btn btn-success btn-advanced-subscription btn-accept"
            href="#" id="asp-go-to">
            <em class="fa fa-external-link"></em>
            {{ "GoToSessionX"|get_plugin_lang('AdvancedSubscriptionPlugin')|format(session.name) }}
        </a>
    </div>
{% endif %}
