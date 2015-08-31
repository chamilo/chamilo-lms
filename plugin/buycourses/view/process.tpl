<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-7">
        <h3 class="page-header">{{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        <div class="row">
            {% if buying_course %}
                <div class="col-sm-6 col-md-5">
                    <p>
                        <img alt="{{ course.title }}" class="img-responsive" src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}">
                    </p>
                    <p class="lead text-right">{{ course.currency }} {{ course.price }}</p>
                </div>
                <div class="col-sm-6 col-md-7">
                    <h3 class="page-header">{{ course.title }}</h3>
                    <ul class="items-teacher list-unstyled">
                        {% for teacher in course.teachers %}
                            <li><i class="fa fa-user"></i> {{ teacher }}</li>
                        {% endfor %}
                    </ul>
                    <p>
                        <a class="ajax btn btn-primary btn-sm" data-title="{{ course.title }}" href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}">
                            {{'Description'|get_lang }}
                        </a>
                    </p>
                </div>
            {% elseif buying_session %}
                <div class="col-sm-6 col-md-5">
                    <p>
                        <img alt="{{ session.name }}" class="img-responsive" src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                    </p>
                    <p class="lead text-right">{{ session.currency }} {{ session.price }}</p>
                </div>
                <div class="col-sm-6 col-md-7">
                    <h3 class="page-header">{{ session.name }}</h3>
                    <p>{{ session.dates.display }}</p>
                    <dl>
                        {% for course in session.courses %}
                            <dt>{{ course.title }}</dt>
                            {% for coach in course.coaches %}
                                <dd><i class="fa fa-user fa-fw"></i> {{ coach }}</dd>
                            {% endfor %}
                        {% endfor %}
                    </dl>
                </div>
            {% endif %}
        </div>
    </div>
    <div class="col-md-5">
        <form action="../src/process_confirm.php" method="post">
            <h3 class="page-header">{{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
            <dl class="dl-horizontal">
                <dt>{{ 'Name'|get_lang }}:</dt>
                <dd>{{ user.complete_name }}</dd>
                <dt>{{ 'User'|get_lang }}:</dt>
                <dd>{{ user.username }}</dd>
                <dt>{{ 'Email'|get_lang }}:</dt>
                <dd>{{ user.email }}</dd>
            </dl>
            <legend align="center">{{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}</legend>
            <div class="form-group">
                {% if paypal_enabled == "true" %}
                    <label class="radio-inline">
                        <input type="radio" name="payment_type" value="PayPal" > <i class="fa fa-fw fa-cc-paypal"></i> Paypal
                    </label>
                {% endif %}

                {% if transfer_enabled == "true" %}
                    <label class="radio-inline">
                        <input type="radio" name="payment_type" value="Transfer" > <i class="fa fa-fw fa-money"></i> {{ 'BankTransfer'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>
                {% endif %}
            </div>
            <div class="form-group">
                <button class="btn btn-success" type="submit">
                    <i class="fa fa-check"></i> {{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                </button>
            </div>
        </form>
    </div>
</div>
