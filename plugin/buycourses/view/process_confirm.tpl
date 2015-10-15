<div class="row">
    <div class="col-sm-6 col-md-5">
        <h3 class="page-header">{{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        <dl class="dl-horizontal">
            <dt>{{ 'Name'|get_lang }}<dt>
            <dd>{{ user.complete_name }}</dd>
            <dt>{{ 'Username'|get_lang }}<dt>
            <dd>{{ user.username }}</dd>
            <dt>{{ 'EmailAddress'|get_lang }}<dt>
            <dd>{{ user.email }}</dd>
        </dl>
    </div>
    <div class="col-sm-6 col-md-7">
        {% if buying_course %}
            <div class="row">
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
                            <li><em class="fa fa-user"></em> {{ teacher }}</li>
                        {% endfor %}
                    </ul>
                    <p>
                        <a class="ajax btn btn-primary btn-sm" data-title="{{ course.title }}" href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}">
                            {{'Description'|get_lang }}
                        </a>
                    </p>
                </div>
            </div>
        {% elseif buying_session %}
            <h3 class="page-header">{{ session.name }}</h3>
            <div class="row">
                <div class="col-sm-12 col-md-5">
                    <p>
                        <img alt="{{ session.name }}" class="img-responsive" src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                    </p>
                    <p class="lead text-right">{{ session.currency }} {{ session.price }}</p>
                </div>
                <div class="col-sm-12 col-md-7">
                    <p>{{ session.dates.display }}</p>
                    <dl>
                        {% for course in session.courses %}
                            <dt>{{ course.title }}</dt>
                            {% for coach in course.coaches %}
                                <dd><em class="fa fa-user fa-fw"></em> {{ coach }}</dd>
                            {% endfor %}
                        {% endfor %}
                    </dl>
                </div>
            </div>
        {% endif %}
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <h3 class="page-header">{{ 'BankAccountInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>{{ 'Name'|get_lang }}</th>
                        <th class="text-center">{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-center">{{ 'SWIFT'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {% for account in transfer_accounts %}
                        <tr>
                            <td>{{ account.name }}</td>
                            <td class="text-center">{{ account.account }}</td>
                            <td class="text-center">{{ account.swift }}</td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
        <p>{{ 'OnceItIsConfirmedYouWillReceiveAnEmailWithTheBankInformationAndAnOrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</p>
    </div>
</div>

{{ form }}
