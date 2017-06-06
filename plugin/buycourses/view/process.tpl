<h2 class="page-header">{{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}</h2>
<div class="row">
    <div class="col-md-5">
        <div class="thumbnail">
            {% if buying_course %}
                <a class="ajax" data-title="{{ course.title }}"
                   href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}">
                    <img alt="{{ course.title }}" class="img-responsive" style="width: 100%;"
                         src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}">
                </a>
                <div class="caption">
                    <h3>
                        <a class="ajax" data-title="{{ course.title }}"
                           href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}">{{ course.title }}</a>
                    </h3>
                    <ul class="fa-ul">
                        {% for teacher in course.teachers %}
                            <li><em class="fa-li fa fa-user" aria-hidden="true"></em>{{ teacher }}</li>
                        {% endfor %}
                    </ul>
                    <p id="n-price" class="lead text-right" style="color: white;">
                        <span class="label label-primary">{{ course.currency == 'BRL' ? 'R$' : course.currency }} {{ course.price }}</span>
                    </p>
                    <p id="s-price" class="lead text-right"></p>
                </div>
            {% elseif buying_session %}
                <img alt="{{ session.name }}" class="img-ressponsive" style="width: 100%;"
                     src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                <div class="caption">
                    <h3>{{ session.name }}</h3>
                    <ul class="fa-ul">
                        <li>
                            <em class="fa-li fa fa-calendar" aria-hidden="true"></em>{{ session.dates.display }}
                        </li>
                    </ul>
                    <ul class="fa-ul">
                        {% for course in session.courses %}
                            <li>
                                <em class="fa-li fa fa-book" aria-hidden="true"></em>
                                {{ course.title }}
                                {% if course.coaches|length %}
                                    <ul class="fa-ul">
                                        {% for coach in course.coaches %}
                                            <li><em class="fa-li fa fa-user" aria-hidden="true"></em>{{ coach }}</li>
                                        {% endfor %}
                                    </ul>
                                {% endif %}
                            </li>
                        {% endfor %}
                    </ul>
                    <p id="n-price" class="lead text-right" style="color: white;">
                        <span class="label label-primary">{{ session.currency == 'BRL' ? 'R$' : session.currency }} {{ session.price }}</span>
                    </p>
                    <p id="s-price" class="lead text-right"></p>
                </div>
            {% elseif buying_service %}
                <a href='{{ _p.web }}service/{{ service.id }}'>
                    <img alt="{{ service.name }}" class="img-responsive"
                         src="{{ service.image ? _p.web ~ 'plugin/buycourses/uploads/services/images/' ~ service.image : 'session_default.png'|icon() }}">
                </a>
                <div class="caption">
                    <h3>
                        <a href='{{ _p.web }}service/{{ service.id }}'>{{ service.name }}</a>
                    </h3>
                    <ul class="fa-ul">
                        {% if service.applies_to %}
                            <li>
                                <em class="fa-li fa fa-hand-o-right" aria-hidden="true"></em>
                                {% if service.applies_to == 0 %}
                                    {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') ~ ' ' ~ 'None'|get_lang }}
                                {% elseif service.applies_to == 1 %}
                                    {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') ~ ' ' ~ 'User'|get_lang }}
                                {% elseif service.applies_to == 2 %}
                                    {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') ~ ' ' ~ 'Course'|get_lang }}
                                {% elseif service.applies_to == 3 %}
                                    {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') ~ ' ' ~ 'Session'|get_lang }}
                                {% elseif service.applies_to == 4 %}
                                    {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') ~ ' ' ~ 'TemplateTitleCertificate'|get_lang }}
                                {% endif %}
                            </li>
                        {% endif %}
                        <li>
                            <em class="fa-li fa fa-money" aria-hidden="true"></em>
                            {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                            : {{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }}
                            / {{ service.duration_days == 0 ? 'NoLimit'|get_lang  : service.duration_days ~ ' ' ~ 'Days'|get_lang }}
                        </li>
                        <li><em class="fa-li fa fa-user" aria-hidden="true"></em> {{ service.owner_name }}</li>
                        <li><em class="fa-li fa fa-align-justify" aria-hidden="true"></em> {{ service.description }}</li>
                    </ul>
                    <p id="n-price" class="lead text-right" style="color: white;">
                        <span class="label label-primary">{{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }}</span>
                    </p>
                    <p id="s-price" class="lead text-right"></p>
                </div>
            {% endif %}
        </div>
    </div>
    <div class="col-md-6 col-md-offset-1">
        <div class="panel panel-default buycourse-panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
            </div>
            <div class="panel-body">
                {{ form }}
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {

        $("label").removeClass('control-label');
        $('.form_required').remove();
        $("small").remove();
        $("label[for=submit]").remove();

    });
</script>
