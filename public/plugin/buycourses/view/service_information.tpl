<div id="service" class="service">
    <div class="page-header">
        <h2>{{ service.name }}</h2>
    </div>
    <section id="service-info">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="service-media">
                            {% if service.video_url %}
                            <div class="service-video">
                                <div class="embed-responsive embed-responsive-16by9">
                                    {{ essence.replace(service.video_url) }}
                                </div>
                            </div>
                            {% elseif service.image %}
                            <div class="service-image">
                                <a href="{{ _p.web }}service/{{ service.id }}">
                                    <img alt="{{ service.name }}" class="img-rounded img-responsive"
                                         src="{{ service.image ? service.image : 'session_default.png'|icon() }}">
                                </a>
                            </div>
                            {% endif %}
                        </div>
                        <div class="share-social-media">
                            <ul class="sharing-buttons">
                                <li>
                                    {{ "ShareWithYourFriends"|get_lang }}
                                </li>
                                <li>
                                    <a href="https://www.facebook.com/sharer/sharer.php?{{ {'u': pageUrl}|url_encode }}"
                                       target="_blank" class="btn btn-facebook btn-inverse btn-xs">
                                        <em class="fa fa-facebook"></em> Facebook
                                    </a>
                                </li>
                                <li>
                                    <a href="https://twitter.com/home?{{ {'status': session.getName() ~ ' ' ~ pageUrl}|url_encode }}"
                                       target="_blank" class="btn btn-twitter btn-inverse btn-xs">
                                        <em class="fa fa-twitter"></em> Twitter
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': pageUrl, 'title': session.getName() }|url_encode }}"
                                       target="_blank" class="btn btn-linkedin btn-inverse btn-xs">
                                        <em class="fa fa-linkedin"></em> Linkedin
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        {% if service.description %}
                        <div class="description">
                            {{ service.description }}
                        </div>
                        {% endif %}
                        <div class="service-details">
                            {% if service.applies_to != 0 %}
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
                            {% endif %}
                        </div>
                        <div class="service-buy">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="price">
                                        {{ 'Total'|get_lang }}
                                        {{ service.total_price_formatted }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <a href="{{ _p.web_plugin ~ 'buycourses/src/service_process.php?t=4&i=' ~ service.id }}"
                                       class="btn btn-success btn-lg btn-block">
                                        <em class="fa fa-shopping-cart"></em> {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="page-header">
                        <h3>{{ 'ServiceInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
                    </div>
                    <div class="service-information">
                        {{ service.service_information }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
