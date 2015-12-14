<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

{{ curency_form }}

<p class="alert alert-info">
    {{ 'PluginInstruction'|get_plugin_lang('BuyCoursesPlugin') }}
    <a href="{{ _p.web_main }}admin/configure_plugin.php?name=buycourses">{{ 'ClickHere'|get_plugin_lang('BuyCoursesPlugin') }}</a>
</p>

{% if paypal_enable == "true" %}
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ 'PayPalConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-5">
                    <p>{{ 'InfoApiCredentials'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                    <ul>
                        <li>{{ 'InfoApiStepOne'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                        <li>{{ 'InfoApiStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                        <li>{{ 'InfoApiStepThree'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                    </ul>
                </div>
                <div class="col-md-7">
                    {{ paypal_form }}
                </div>
            </div>
        </div>
    </div>
{% endif %}

{% if commissions_enable == "true" %}
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ 'CommissionsConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-5">
                    <p>{{ 'InfoCommissions'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                </div>
                <div class="col-md-7">
                    {{ commission_form }}
                </div>
            </div>
        </div>
    </div>
{% endif %}

{% if transfer_enable == "true" %}
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ 'TransfersConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-5">
                    {{ transfer_form }}
                </div>
                <div class="col-md-7">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ 'Name'|get_lang }}</th>
                                    <th>{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                                    <th>{{ 'SWIFT'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                                    <th>{{ 'Actions'|get_lang }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {% for account in transfer_accounts %}
                                    <tr>
                                        <td>{{ account.name }}</td>
                                        <td>{{ account.account }}</td>
                                        <td>{{ account.swift }}</td>
                                        <td>
                                            <a href="{{ _p.web_self ~ '?' ~ {'action':'delete_taccount', 'id': account.id}|url_encode() }}" class="btn btn-danger btn-sm">
                                                <em class="fa fa-remove"></em> {{ 'Delete'|get_lang }}
                                            </a>
                                        </td>
                                    </tr>
                                {% endfor %}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endif %}
