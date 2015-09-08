<link rel="stylesheet" type="text/css" href="resources/css/style.css"/>
<div class="row">
    <div class="col-md-12">
        {% if _u.is_admin %}
            <div class="help-bycourse">
                <div class="row">
                    <div class="col-md-7">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h3>{{ 'TitlePlugin'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
                                <p>{{ 'PluginPresentation'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                                <p>&nbsp;</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                {{ 'Instructions'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="panel-body">
                                <ul>
                                    <li>{{ 'InstructionsStepOne'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                                    <li>{{ 'InstructionsStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                                    <li>{{ 'InstructionsStepThree'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {% endif %}

        <div class="row">
            <div class="col-md-3">
                <div class="thumbnail">
                    <a href="src/list.php">
                        <img src="resources/img/128/buycourses.png">
                    </a>
                    <div class="caption">
                        <a class="btn btn-default btn-sm" href="src/list.php">{{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </div>
                </div>
            </div>

            {% if _u.is_admin %}
                <div class="col-md-3">
                    <div class="thumbnail">
                        <a href="src/configuration.php">
                            <img src="resources/img/128/settings.png">
                        </a>
                        <div class="caption">
                            <a class="btn btn-default btn-sm" href="src/configuration.php">{{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="thumbnail">
                        <a href="src/paymentsetup.php">
                            <img src="resources/img/128/paymentsettings.png">
                        </a>
                        <div class="caption">
                            <a class="btn btn-default btn-sm" href="src/paymentsetup.php">{{ 'ConfigurationOfPayments'|get_plugin_lang('BuyCoursesPlugin') }} </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="thumbnail">
                        <a href="src/pending_orders.php">
                            <img src="resources/img/128/backlogs.png">
                        </a>
                        <div class="caption">
                            <a class="btn btn-default btn-sm" href="src/pending_orders.php"> {{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }} </a>
                        </div>
                    </div>
                </div>
            {% endif %}
        </div>
    </div>
</div>
