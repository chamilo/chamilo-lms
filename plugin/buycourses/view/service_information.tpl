<link rel="stylesheet" type="text/css" href="{{ _p.web_plugin ~ 'buycourses/resources/css/style.css' }}"/>

<div id="service-information">

    <div class="row">
        <div class="col-xs-12">
            <h3 class="text-uppercase buy-courses-title-color">{{ service.name }}</h3>
        </div>

        {% if service.video_url %}
            <div class="col-sm-6 col-md-7 col-xs-12">
                <div class="embed-responsive embed-responsive-16by9">
                    {{ essence.replace(service.video_url) }}
                </div>
            </div>
        {% endif %}

        <div class="{{ service.video_url ? 'col-sm-6 col-md-5 col-xs-12' : 'col-sm-12 col-xs-12' }}">
            <div class="block">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ "Description"|get_lang }}</h4>
                    </div>
                    <div class="panel-body">
                        <p><em class="fa fa-flag-o"></em> <b>{{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}</b> :
                            {% if service.applies_to == 1 %}
                                {{ 'User'|get_lang }}
                            {% elseif service.applies_to == 2 %}
                                {{ 'Course'|get_lang }}
                            {% elseif service.applies_to == 3 %}
                                {{ 'Session'|get_lang }}
                            {% elseif service.applies_to == 4 %}
                                {{ 'TemplateTitleCertificate'|get_lang }}
                            {% endif %}
                        </p>
                        <p><em class="fa fa-money"></em> <b>{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</b>
                            : {{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }}</p>
                        <p><em class="fa fa-align-justify"></em> <b>{{ 'Details'|get_lang }}</b>
                            : {{ service.description }}</p>
                        <div class="text-right" style="padding-bottom: 20px;">
                            <a href="{{ _p.web_plugin ~ 'buycourses/src/service_process.php?t=4&i=' ~ service.id }}"
                               class="btn btn-success btn-lg">
                                <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </br>
    </br>
    <div class="row info-course">
        <div class="col-xs-12 col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>{{ 'ServiceInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h4>
                </div>
                <div class="panel-body">
                    {{ service.service_information }}
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-5">
            <div class="panel panel-default social-share">
                <div class="panel-heading">
                    <h4>{{ "ShareWithYourFriends"|get_lang }}</h4>
                </div>
                <div class="panel-body">
                    <div class="icons-social text-center">
                        <a href="https://www.facebook.com/sharer/sharer.php?{{ {'u': pageUrl}|url_encode }}"
                           target="_blank" class="btn bnt-link btn-lg">
                            <em class="fa fa-facebook fa-2x"></em>
                        </a>
                        <a href="https://twitter.com/home?{{ {'status': session.getName() ~ ' ' ~ pageUrl}|url_encode }}"
                           target="_blank" class="btn bnt-link btn-lg">
                            <em class="fa fa-twitter fa-2x"></em>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': pageUrl, 'title': session.getName() }|url_encode }}"
                           target="_blank" class="btn bnt-link btn-lg">
                            <em class="fa fa-linkedin fa-2x"></em>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
