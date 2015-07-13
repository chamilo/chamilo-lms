<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-12">
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">Datos de la Compra</div>
                <div class="panel-body">

                    {% if isSession == "YES" %}
                        <div class="items-session">
                            <div class="picture">

                            </div>
                            <div class="title">
                                <h3>{{ title }}</h3>
                                <p>{{ 'From'|get_lang }} {{ session.access_start_date }} {{ 'To'|get_lang }} {{ session.access_end_date }}</p>
                            </div>
                            <div class="list-course">
                                <ul>
                                    {% for course in session.courses %}
                                        <li>{{ course.title }}
                                        {% if course.enrolled == "YES" %}
                                            <span>{{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                        {% endif %}
                                        {% if course.enrolled == "TMP" %}
                                            <span>{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                        {% endif %}
                                        </li>
                                    {% endfor %}
                                </ul>
                            </div>
                            <div class="info">
                                <a class="ajax btn btn-primary" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                        {{ 'Description'|get_lang }}
                                </a>
                            </div>
                        </div>
                    {% else %}
                        <div class="items-course">
                            <div class="picture">
                                <a class="ajax" rel="gb_page_center[778]" title=""
                                   href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                    <img alt="" src="{{ server }}{{ course.course_img }}">
                                </a>
                            </div>
                            <div class="title">
                                <h3>{{ course.title }}</h3>
                                <p>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</p>
                            </div>
                            <div class="price">{{ course.price }} {{ currency }}</div>
                            <div class="btn-toolbar">
                                <a class="ajax btn btn-primary" title=""
                                   href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">{{'Description'|get_lang }}
                                </a>
                            </div>
                        </div>
                    {% endif %}
                </div>
            </div>
        </div>
        <div id="course_category_well" class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="panel-body">
                    <div class="user-profile">
                        <dl class="dl-horizontal">
                            <dt>{{ 'Name'|get_lang }}:</dt>
                            <dd>{{ name }}</dd>
                            <dt>{{ 'User'|get_lang }}:</dt>
                            <dd>{{ user }}</dd>
                            <dt>{{ 'Email'|get_lang }}:</dt>
                            <dd>{{ email }}</dd>
                        </dl>
                    </div>
                    <form class="form-horizontal" action="../src/process_confirm.php" method="post">

                            <legend align="center">{{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}</legend>

                                <div class="form-group">
                                    {% if paypal_enable == "true" %}
                                    <div class="checkbox">
                                        <label>
                                            <input type="radio" id="payment_type-p" name="payment_type" value="PayPal" > Paypal
                                        </label>
                                    </div>
                                    {% endif %}
                                    {% if transfer_enable == "true" %}
                                    <div class="checkbox">
                                        <label>
                                            <input type="radio" id="payment_type-tra" name="payment_type" value="Transfer" > {{ 'BankTransfer'|get_plugin_lang('BuyCoursesPlugin') }}
                                        </label>
                                    </div>
                                    {% endif %}
                                </div>

                                <input type="hidden" name="currency_type" value="{{ currency }}" />
                                <input type="hidden" name="server" value="{{ server }}"/>
                                <input align="center" type="submit" class="btn btn-success" value="{{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}"/>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
