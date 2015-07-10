<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-12">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">Datos de la Compra</div>
                <div class="panel-body">



                </div>
            </div>
        </div>
        <div id="course_category_well" class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="panel-body">
                    <dl class="dl-horizontal">
                        <dt>{{ 'Name'|get_lang }}:</dt>
                        <dd>{{ name }}</dd>
                        <dt>{{ 'User'|get_lang }}:</dt>
                        <dd>{{ user }}</dd>
                        <dt>{{ 'Email'|get_lang }}:</dt>
                        <dd>{{ email }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-8">
        {% if isSession == "YES" %}
            <div class="row">
                <div class="span4">
                    <div class="categories-course-description">
                        <h3>{{ title }}</h3>
                        <h5>{{ 'From'|get_lang }} {{ session.access_start_date }} {{ 'To'|get_lang }} {{ session.access_end_date }}</h5>
                    </div>
                </div>
                <div class="span right">
                    <div class="sprice right">
                        {{ session.price }} {{ currency }}
                    </div>
                    <div class="cleared"></div>
                </div>
            </div>
            {% for course in session.courses %}
                <div class="row">
                    <div class="span">
                        <div class="thumbnail">
                            <a class="ajax" rel="gb_page_center[778]" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                <img alt="" src="{{ server }}{{ course.course_img }}">
                            </a>
                        </div>
                    </div>
                    <div class="span4">
                        <div class="categories-course-description">
                            <h3>{{ course.title }}</h3>
                            <h5>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</h5>
                        </div>
                        {% if course.enrolled == "YES" %}
                            <span class="label label-info">{{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                        {% endif %}
                        {% if course.enrolled == "TMP" %}
                            <span class="label label-warning">{{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                        {% endif %}
                    </div>
                    <div class="span right">
                        <div class="cleared"></div>
                        <div class="btn-toolbar right">
                            <a class="ajax btn btn-primary" title="" href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                                {{ 'Description'|get_lang }}
                            </a>
                        </div>
                    </div>
                </div>
            {% endfor %}
        {% else %}
            <div class="row">
                <div class="span">
                    <div class="thumbnail">
                        <a class="ajax" rel="gb_page_center[778]" title=""
                           href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                            <img alt="" src="{{ server }}{{ course.course_img }}">
                        </a>
                    </div>
                </div>
                <div class="span4">
                    <div class="categories-course-description">
                        <h3>{{ course.title }}</h3>
                        <h5>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</h5>
                    </div>
                </div>
                <div class="span right">
                    <div class="sprice right">{{ course.price }} {{ currency }}</div>
                    <div class="cleared"></div>
                    <div class="btn-toolbar right">
                        <a class="ajax btn btn-primary" title=""
                           href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">{{'Description'|get_lang }}
                        </a>
                    </div>
                </div>
            </div>
        {% endif %}
        </div>
    </div>
    <div class="cleared"></div>
    <form class="form-horizontal span3 offset4" action="../src/process_confirm.php" method="post">
        <fieldset>
            <legend align="center">{{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}</legend>
            <div align="center" class="control-group">
                <div class="controls margin-left-fifty">
                    {% if paypal_enable == "true" %}
                        <label class="radio">
                            <input type="radio" id="payment_type-p" name="payment_type" value="PayPal" > Paypal
                        </label>
                    {% endif %}
                    {% if transfer_enable == "true" %}
                        <label class="radio">
                            <input type="radio" id="payment_type-tra" name="payment_type" value="Transfer" > {{ 'BankTransfer'|get_plugin_lang('BuyCoursesPlugin') }}
                        </label>
                    {% endif %}
                </div>
                </br>
                <input type="hidden" name="currency_type" value="{{ currency }}" />
                <input type="hidden" name="server" value="{{ server }}"/>
                <input align="center" type="submit" class="btn btn-success" value="{{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}"/>
            </div>
        </fieldset>
    </form>
    <div class="cleared"></div>
</div>
