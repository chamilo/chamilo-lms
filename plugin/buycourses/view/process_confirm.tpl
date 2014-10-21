<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <div id="course_category_well" class="well span3">
            <ul class="nav nav-list">
                <li class="nav-header"><h4>{{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}:</h4></li>
                <li class="nav-header">{{ 'Name'|get_lang }}:</li>
                <li><h5>{{ name | e }}</h5></li>
                <li class="nav-header">{{ 'User'|get_lang }}:</li>
                <li><h5>{{ user | e }}</h5></li>
                <li class="nav-header">{{ 'Email'|get_lang }}:</li>
                <li><h5>{{ email | e}}</h5></li>
                <br/>
            </ul>
        </div>

        <br/><br/>

        <div class="well_border span8">
            <div class="row">
                <div class="span">
                    <div class="thumbnail">
                        <a class="ajax" rel="gb_page_center[778]" title=""
                           href="{{ server }}plugin/buycourses/src/ajax.php?code={{ course.code }}">
                            <img src="{{ server }}{{ course.course_img }}">
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
        </div>
    </div>
    <div class="cleared"></div>
    <hr/>
    <div align="center">
        <table class="data_table" style="width:70%">
            <tr>
                <th class="ta-center">{{ 'BankAccountInformation'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            </tr>
            {% set i = 0 %}
            {% for account in accounts %}
            {{ i%2==0 ? '<tr class="row_even">' : '<tr class="row_odd">' }}
                {% set i = i + 1 %}
                <td class="ta-center">
                <font color="#0000FF">{{ account.name | e }}</font><br/>
                {% if account.swift != '' %}
                SWIFT: <strong>{{ account.swift | e }}</strong><br/>
                {% endif %}
                {{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}: <strong>{{ account.account | e }}</strong><br/>
                </td></tr>
            {% endfor %}
            </table>
            <br />
            <div class="normal-message">{{ 'OnceItIsConfirmed,YouWillReceiveAnEmailWithTheBankInformationAndAnOrderReference'|get_plugin_lang('BuyCoursesPlugin') | e}}
    </div>
    <br/>

    <form method="post" name="frmConfirm" action="../src/process_confirm.php">
        <input type="hidden" name="payment_type" value="Transfer"/>
        <input type="hidden" name="name" value="{{ name | e }}"/>
        <input type="hidden" name="price" value="{{ course.price }}"/>
        <input type="hidden" name="title" value="{{ course.title | e }}"/>

        <div class="btn_next">
            <input class="btn btn-success" type="submit" name="Confirm" value="{{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}"/>
            <input class="btn btn-danger" type="button" name="Cancel" value="{{ 'CancelOrder'|get_plugin_lang('BuyCoursesPlugin') }}" id="CancelOrder"/>
        </div>
    </form>
</div>
<div class="cleared"></div>
</div>
