<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-3">
        <div id="course_category_well" class="well">
            <h4>{{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h4>
            <dl>
                <dt>{{ 'Name'|get_lang }}</dt>
                <dd>{{ name|e }}</dd>
                <dt>{{ 'User'|get_lang }}</dt>
                <dd>{{ user | e }}</dd>
                <dt>{{ 'Email'|get_lang }}</dt>
                <dd>{{ email | e}}</dd>
            </dl>
        </div>
    </div>

    <div class="col-md-9">
        <div class="well">
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
</div>

<hr/>

<div class="row">
    <div class="col-md-5 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{{ 'BankAccountInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
            </div>
            <div class="panel-body">
                {% for account in accounts %}
                    <p class="lead text-center">{{ account.name | e }}</p>

                    <dl class="dl-horizontal">
                        {% if account.swift != '' %}
                            <dt>SWIFT</dt>
                            <dd>{{ account.swift | e }}</dd>
                        {% endif %}

                        <dt>{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
                        <dd>{{ account.account }}</dd>
                    </dl>

                    {% if lopp.index > 1 %}
                        <hr>
                    {% endif %}
                {% endfor %}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            {{ 'OnceItIsConfirmed,YouWillReceiveAnEmailWithTheBankInformationAndAnOrderReference'|get_plugin_lang('BuyCoursesPlugin') }}
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 col-md-offset-4">
        <hr>

        <form method="post" name="frmConfirm" action="../src/process_confirm.php">
            <input type="hidden" name="payment_type" value="Transfer"/>
            <input type="hidden" name="name" value="{{ name | e }}"/>
            <input type="hidden" name="price" value="{{ course.price }}"/>
            <input type="hidden" name="title" value="{{ course.title | e }}"/>

            <p class="text-center">
                <input class="btn btn-success" type="submit" name="Confirm" value="{{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}"/>
                <input class="btn btn-danger" type="button" name="Cancel" value="{{ 'CancelOrder'|get_plugin_lang('BuyCoursesPlugin') }}" id="CancelOrder"/>
            </p>
        </form>
    </div>
</div>
