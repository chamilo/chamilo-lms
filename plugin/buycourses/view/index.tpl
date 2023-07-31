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
    <div class="col-md-4">
        <div class="thumbnail">
            <a href="src/course_catalog.php">
                <img src="resources/img/128/buycourses.png">
            </a>
            <div class="caption">
                <p class="text-center">
                    <a class="btn btn-default btn-sm"
                       href="src/course_catalog.php">{{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="thumbnail">
            <a href="src/subscription_course_catalog.php">
                <img src="resources/img/128/buysubscriptions.png">
            </a>
            <div class="caption">
                <p class="text-center">
                    <a class="btn btn-default btn-sm"
                       href="src/subscription_course_catalog.php">{{ 'BuySubscriptions'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                </p>
            </div>
        </div>
    </div>

    {% if _u.is_admin %}
        <div class="col-md-4">
            <div class="thumbnail">
                <a href="src/list.php">
                    <img src="resources/img/128/settings.png">
                </a>
                <div class="caption">
                    <p class="text-center">
                        <a class="btn btn-default btn-sm"
                           href="src/list.php">{{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="thumbnail">
                <a href="src/subscriptions_courses.php">
                    <img src="resources/img/128/subscriptionssettings.png">
                </a>
                <div class="caption">
                    <p class="text-center">
                        <a class="btn btn-default btn-sm"
                           href="src/subscriptions_courses.php">{{ 'ConfigurationOfSubscriptionsAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="thumbnail">
                <a href="src/coupons.php">
                    <img src="resources/img/128/discount.png">
                </a>
                <div class="caption">
                    <p class="text-center">
                        <a class="btn btn-default btn-sm"
                           href="src/coupons.php">{{ 'CouponsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="thumbnail">
                <a href="src/paymentsetup.php">
                    <img src="resources/img/128/paymentsettings.png">
                </a>
                <div class="caption">
                    <p class="text-center">
                        <a class="btn btn-default btn-sm"
                           href="src/paymentsetup.php">{{ 'PaymentsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="thumbnail">
                <a href="src/sales_report.php">
                    <img src="resources/img/128/backlogs.png">
                </a>
                <div class="caption">
                    <p class="text-center">
                        <a class="btn btn-default btn-sm"
                           href="src/sales_report.php"> {{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                    </p>
                </div>
            </div>
        </div>
    {% endif %}
</div>
