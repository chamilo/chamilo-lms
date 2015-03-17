<link rel="stylesheet" type="text/css" href="resources/css/style.css"/>
<div class="row">
    <div class="span12">
        <div class="row">
        {% if isAdmin == 'true' %}
            <div class="span12">
                <div>
                    <h2>{{ 'TitlePlugin'|get_plugin_lang('BuyCoursesPlugin') }}</h2>
                    <p>{{ 'PluginPresentation'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                </div>
                <div class="normal-message">
                    <h3>{{ 'Instructions'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
                    <ul>
                        <li>{{ 'InstructionsStepOne'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                        <li>{{ 'InstructionsStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                        <li>{{ 'InstructionsStepThree'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                    </ul>
                </div>
            </div>
        {% endif %}
            <div class="span12">

            </div>
        </div>
        <div class="row">

            <div class="span3">
                <div class="thumbnail">
                    <a href="src/list.php">
                        <img src="resources/img/128/buycourses.png">
                    </a>
                    <div class="caption">
                        <a class="btn" href="src/list.php">{{ BuyCourses }}</a>
                    </div>
                </div>
            </div>
            {% if isAdmin == 'true' %}
            <div class="span3">
                <div class="thumbnail">
                    <a href="src/configuration.php">
                        <img src="resources/img/128/settings.png">
                    </a>
                    <div class="caption">
                        <a class="btn" href="src/configuration.php">{{ ConfigurationOfCoursesAndPrices }}</a>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="thumbnail">
                    <a href="src/paymentsetup.php">
                        <img src="resources/img/128/paymentsettings.png">
                    </a>
                    <div class="caption">
                        <a class="btn" href="src/paymentsetup.php">{{ ConfigurationOfPayments }} </a>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="thumbnail">
                    <a href="src/pending_orders.php">
                        <img src="resources/img/128/backlogs.png">
                    </a>
                    <div class="caption">
                        <a class="btn" href="src/pending_orders.php"> {{ OrdersPendingOfPayment }} </a>
                    </div>
                </div>
            </div>
            {% endif %}
        </div>
    </div>
</div>
