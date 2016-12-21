<div class="row">
    <div class="col-md-5 panel panel-default buycourse-panel-default">
        <h3 class="panel-heading">{{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        <legend></legend>
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
                            <li><em class="fa fa-user"></em> {{ teacher }}</li>
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
                                <dd><em class="fa fa-user fa-fw"></em> {{ coach }}</dd>
                            {% endfor %}
                        {% endfor %}
                    </dl>
                </div>
            {% elseif buying_service %}
                <div class="col-sm-12 col-md-12 col-xs-12">
                    <a href='{{ _p.web }}service/{{ service.id }}'>
                        <img alt="{{ service.name }}" class="img-responsive" src="{{ _p.web }}plugin/buycourses/uploads/services/images/{{ service.image }}">
                    </a>
                </div>
                <div class="col-sm-12 col-md-12 col-xs-12">
                    <h3>
                        <a href='{{ _p.web }}service/{{ service.id }}'>{{ service.name }}</a>
                    </h3>
                    <ul class="list-unstyled">
                        {% if service.applies_to == 0 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'None' | get_lang }}</li>
                        {% elseif service.applies_to == 1 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'User' | get_lang }}</li>
                        {% elseif service.applies_to == 2 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Course' | get_lang }}</li>
                        {% elseif service.applies_to == 3 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Session' | get_lang }}</li>
                        {% endif %}
                        <li><em class="fa fa-money"></em> {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }} : {{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }} / {{ service.duration_days == 0 ? 'NoLimit' | get_lang  : service.duration_days ~ ' ' ~ 'Days' | get_lang }} </li>
                        <li><em class="fa fa-user"></em> {{ service.owner_name }}</li>
                        <li><em class="fa fa-align-justify"></em> {{ service.description }}</li>
                    </ul>
                    <p id="n-price" class="lead text-right" style="color: white;"><span class="label label-primary">{{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }}</span></p>
                    <p id="s-price" class="lead text-right"></p>
                </div>
                <script>
                    $(document).ready(function() {

                        $("label").removeClass('control-label');
                        $('.form_required').remove();
                        $("small").remove();
                        $("label[for=submit]").remove();

                    });
                </script>
            {% endif %}
        </div>
    </div>
    <div class="col-md-1">
    </div>
    <div class="col-md-6 panel panel-default buycourse-panel-default">
        <h3 class="panel-heading">{{ 'PaymentMethods' | get_plugin_lang('BuyCoursesPlugin') }}</h3>
        {{ form }}
    </div>
</div>
