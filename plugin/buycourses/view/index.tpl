<link rel="stylesheet" type="text/css" href="resources/css/style.css"/>
{% if _u.is_admin %}
    <div class="row">
        <div class="col-md-12">
            <article class="jumbotron">
                <h3>{{ 'TitlePlugin'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
                <p>{{ 'PluginPresentation'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                <ul class="list-unstyled">
                    <li>
                        {{ 'Instructions'|get_plugin_lang('BuyCoursesPlugin') }}
                        <ul>
                            <li>{{ 'InstructionsStepOne'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                            <li>{{ 'InstructionsStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                            <li>{{ 'InstructionsStepThree'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                        </ul>
                    </li>
                </ul>
            </article>
        </div>
    </div>
{% endif %}

<div class="row">
    <div class="col-md-3">
        <div class="thumbnail">
            <a href="src/course_catalog.php">
                <img src="resources/img/128/buycourses.png">
            </a>
            <div class="caption">
                <p class="text-center">
                    <a class="btn btn-default btn-sm" href="src/course_catalog.php">{{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                </p>
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
                    <p class="text-center">
                        <a class="btn btn-default btn-sm" href="src/configuration.php">{{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="thumbnail">
                <a href="src/paymentsetup.php">
                    <img src="resources/img/128/paymentsettings.png">
                </a>
                <div class="caption">
                    <p class="text-center">
                        <a class="btn btn-default btn-sm" href="src/paymentsetup.php">{{ 'PaymentsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="thumbnail">
                <a href="src/sales_report.php">
                    <img src="resources/img/128/backlogs.png">
                </a>
                <div class="caption">
                    <p class="text-center">
                        <a class="btn btn-default btn-sm" href="src/sales_report.php"> {{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
    {% endif %}
</div>
