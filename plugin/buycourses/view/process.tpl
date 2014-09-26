<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <div id="course_category_well" class="well span3">
            <ul class="nav nav-list">
                <li class="nav-header"><h4>{{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}:</h4></li>
                <li class="nav-header">{{ 'Name'|get_lang }}:</li>
                <li><h5>{{ name }}</h5></li>
                <li class="nav-header">{{ 'User'|get_lang }}:</li>
                <li><h5>{{ user }}</h5></li>
                <li class="nav-header">{{ 'Email'|get_lang }}:</li>
                <li><h5>{{ email }}</h5></li>
                <br/>
            </ul>
        </div>

        <br/><br/>
        <div class="well_border span8">
        {% if isSession == "YES" %}
            <div class="row">
                <div class="span4">
                    <div class="categories-course-description">
                        <h3>{{ title }}</h3>                                
                        <h5>{{ 'From'|get_lang }} {{ session.date_start }} {{ 'To'|get_lang }} {{ session.date_end }}</h5>
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
