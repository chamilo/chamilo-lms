<section id="about-course">
    {% if is_subscribed %}
        <div class="alert alert-warning">
            <span class="fa fa-info-circle" aria-hidden="true"></span>
            <strong>
                {% if session.duration and user_session_time < 1 %}
                    {{ 'YourSessionTimeIsExpired'|get_lang }}
                {% else %}
                    {{ 'AlreadyRegisteredToSession'|get_lang }}
                {% endif %}
            </strong>
        </div>
    {% endif %}
    <section class="session">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="session-title">{{ session.name }}</h2>
                        {% if show_tutor and session.generalCoach %}
                            <div class="session-tutor">
                                <em class="fa fa-user"></em> {{ 'SessionGeneralCoach'|get_lang }}:
                                <em>{{ session.generalCoach.getCompleteName() }}</em>
                            </div>
                        {% endif %}
                        {% if session.getShowDescription() %}
                            <div class="session-description">
                                {{ session.getDescription() }}
                            </div>
                        {% endif %}

                        {% if not 'hide_social_media_links'|api_get_configuration_value %}
                        <div class="share-social-media">
                            <ul class="sharing-buttons">
                                <li>
                                    {{ "ShareWithYourFriends"|get_lang }}
                                </li>
                                <li>
                                    <a href="https://www.facebook.com/sharer/sharer.php?{{ {'u': page_url }|url_encode }}"
                                       target="_blank" class="btn btn-facebook btn-inverse btn-xs">
                                        <em class="fa fa-facebook"></em> Facebook
                                    </a>
                                </li>
                                <li>
                                    <a href="https://twitter.com/home?{{ {'status': session.getName() ~ ' ' ~ page_url }|url_encode }}"
                                       target="_blank" class="btn btn-twitter btn-inverse btn-xs">
                                        <em class="fa fa-twitter"></em> Twitter
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': page_url , 'title': session.getName() }|url_encode }}"
                                       target="_blank" class="btn btn-linkedin btn-inverse btn-xs">
                                        <em class="fa fa-linkedin"></em> Linkedin
                                    </a>
                                </li>
                            </ul>
                        </div>
                        {% endif %}
                    </div>
                    <div class="col-md-4">
                        <div class="session-info">
                            <div class="date">
                                <p>
                                    {% if session.duration %}
                                        {{ 'SessionDurationXDaysTotal'|get_lang|format(session.duration) }}
                                    {% else %}
                                        {{ session_date.display }}
                                    {% endif %}
                                </p>
                            </div>
                            {% if is_premium == false %}
                                <h5>{{ 'CourseSubscription'|get_lang }}</h5>
                                <div class="session-subscribe">
                                    {% if _u.logged and not is_subscribed %}
                                        {{ subscribe_button }}
                                    {% elseif not _u.logged %}
                                        {% if 'allow_registration'|api_get_setting != 'false' %}
                                            <a href="{{ _p.web_main ~ 'auth/inscription.php' ~ redirect_to_session }}"
                                               class="btn btn-success btn-block btn-lg">
                                                <i class="fa fa-pencil" aria-hidden="true"></i> {{ 'SignUp'|get_lang }}
                                            </a>
                                        {% endif %}
                                    {% endif %}
                                </div>
                            {% else %}
                                <div class="session-price">
                                    <div class="sale-price">
                                        {{ 'SalePrice'|get_lang }}
                                    </div>
                                    <div class="price-text">
                                        {{ is_premium.total_price_formatted }}
                                    </div>
                                    {% if not is_subscribed %}
                                        <div class="buy-box">
                                            <a href="{{ _p.web }}plugin/buycourses/src/process.php?i={{ is_premium.product_id }}&t={{ is_premium.product_type }}"
                                               class="btn btn-lg btn-primary btn-block">{{ 'BuyNow'|get_lang }}</a>
                                        </div>
                                    {% endif %}
                                </div>
                            {% endif %}
                            {% if has_requirements %}
                                <div class="session-requirements">
                                    <h5>{{ 'RequiredSessions'|get_lang }}</h5>
                                    {% for sequence in sequences %}
                                        {% if sequence.requirements %}
                                            <p>
                                                {{ sequence.name }} :
                                                {% for requirement in sequence.requirements %}
                                                    <a href="{{ _p.web ~ 'session/' ~ requirement.getId ~ '/about/' }}">
                                                        {{ requirement.getName }}
                                                    </a>
                                                {% endfor %}
                                            </p>
                                        {% endif %}
                                    {% endfor %}
                                </div>
                            {% endif %}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {% for course_data in courses %}
        {% set course_video = '' %}
        {% for extra_field in course_data.extra_fields %}
            {% if extra_field.value.getField().getVariable() == 'video_url' %}
                {% set course_video = extra_field.value.getValue() %}
            {% endif %}
        {% endfor %}
        <div class="panel panel-default panel-course">
            <div class="panel-body">
                <div class="row">
                    <div class="col-sm-5">
                        {% if course_video %}
                            <div class="embed-responsive embed-responsive-16by9">
                                {{ essence.replace(course_video) }}
                            </div>
                        {% else %}
                            <div class="course-image">
                                <img src="{{ course_data.image }}" class="img-rounded img-responsive" width="100%">
                            </div>
                        {% endif %}
                    </div>
                    <div class="col-sm-7">
                        {% if courses|length > 1 %}
                            <div class="course-title">
                                <h3>{{ course_data.course.getTitle }}</h3>
                            </div>
                        {% endif %}
                        <div class="course-description">
                            {% for description in course_data.description %}
                                {{ description.content }}
                            {% endfor %}
                        </div>
                    </div>
                </div>
                {% if course_data.tags %}
                    <div class="panel-tags">
                        <ul class="list-inline course-tags">
                            <li>{{ 'Tags'|get_lang }} :</li>
                            {% for tag in course_data.tags %}
                                <li class="tag-value">
                                    <span>{{ tag.getTag }}</span>
                                </li>
                            {% endfor %}
                        </ul>
                    </div>
                {% endif %}
            </div>
        </div>
        <section class="course">
            <div class="row">
                <div class="{{ course_data.coaches is empty ? 'col-md-12' : 'col-md-8' }}">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <h3 class="sub-title">{{ "CourseInformation"|get_lang }}</h3>
                            <div class="course-information read-more-area">
                                {% if course_data.objectives %}
                                    {% for objectives in course_data.objectives %}
                                        <div class="topics">
                                            <h4 class="title-info">
                                                <em class="fa fa-book"></em> {{ objectives.title }}
                                            </h4>
                                            <div class="content-info">
                                                {{ objectives.content }}
                                            </div>
                                        </div>
                                    {% endfor %}
                                {% endif %}
                                {% if course_data.topics %}
                                    {% for topics in course_data.topics %}
                                        <div class="topics">
                                            <h4 class="title-info">
                                                <em class="fa fa-book"></em> {{ topics.title }}
                                            </h4>
                                            <div class="content-info">
                                                {{ topics.content }}
                                            </div>
                                        </div>
                                    {% endfor %}
                                {% endif %}

                                {% if course_data.methodology %}
                                    {% for methodology in course_data.methodology %}
                                        <div class="topics">
                                            <h4 class="title-info">
                                                <em class="fa fa-book"></em>
                                                {{ methodology.title }}
                                            </h4>
                                            <div class="content-info">
                                                {{ methodology.content }}
                                            </div>
                                        </div>
                                    {% endfor %}
                                {% endif %}

                                {% if course_data.material %}
                                    {% for material in course_data.material %}
                                        <div class="topics">
                                            <h4 class="title-info"><em
                                                        class="fa fa-book"></em> {{ material.title }}</h4>
                                            <div class="content-info">
                                                {{ material.content }}
                                            </div>
                                        </div>
                                    {% endfor %}
                                {% endif %}

                                {% if course_data.resources %}
                                    {% for resources in course_data.resources %}
                                        <div class="topics">
                                            <h4 class="title-info">
                                                <em class="fa fa-book"></em>
                                                {{ resources.title }}
                                            </h4>
                                            <div class="content-info">
                                                {{ resources.content }}
                                            </div>
                                        </div>
                                    {% endfor %}
                                {% endif %}

                                {% if course_data.assessment %}
                                    {% for assessment in course_data.assessment %}
                                        <div class="topics">
                                            <h4 class="title-info">
                                                <em class="fa fa-book"></em> {{ assessment.title }}
                                            </h4>
                                            <div class="content-info">
                                                {{ assessment.content }}
                                            </div>
                                        </div>
                                    {% endfor %}
                                {% endif %}
                                {% if course_data.custom %}
                                    {% for custom in course_data.custom %}
                                        <div class="topics">
                                            <h4 class="title-info">
                                                <em class="fa fa-book"></em> {{ custom.title }}
                                            </h4>
                                            <div class="content-info">
                                                {{ custom.content }}
                                            </div>
                                        </div>
                                    {% endfor %}
                                {% endif %}
                            </div>
                        </div>
                    </div>
                </div>
                {% if course_data.coaches %}
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="panel-teachers">
                                    <h3 class="sub-title">{{ "Coaches"|get_lang }}</h3>
                                    {% for coach in course_data.coaches %}
                                        <div class="coach-information">
                                            <div class="coach-header">
                                                <div class="coach-avatar">
                                                    <img class="img-circle img-responsive" src="{{ coach.image }}"
                                                         alt="{{ coach.complete_name }}">
                                                </div>
                                                <div class="coach-title">
                                                    <h4>{{ coach.complete_name }}</h4>
                                                    {% if coach.diploma %}
                                                        <p>{{ coach.diploma }}</p>
                                                    {% endif %}
                                                </div>
                                            </div>
                                            {% if coach.openarea %}
                                                <div class="open-area {{ course_data.coaches | length >= 2 ? 'open-more' : ' ' }}">
                                                    <p>{{ coach.openarea }}</p>
                                                </div>
                                            {% endif %}
                                            {% for coach_extra_field in coach.extra_fields %}
                                                {% set coach_field = coach_extra_field.value.field %}
                                                {% set coach_field_value = coach_extra_field.value.value %}
                                                {% if not coach_field_value is empty %}
                                                    <dl class="coach-extrafield">
                                                        <dt class="extrafield_dt dt_{{ coach_field.variable }}">{{ coach_field.displayText }}</dt>
                                                        <dd class="extrafield_dd dd_{{ coach_field.variable }}">{{ coach_extra_field.value.value }}</dd>
                                                    </dl>
                                                {% endif %}
                                            {% endfor %}
                                        </div>
                                    {% endfor %}
                                </div>
                            </div>
                        </div>
                    </div>
                {% endif %}
            </div>
        </section>
    {% endfor %}
    </div>
</section>

<script>
    $(function () {
        $('.course-information').readmore({
            speed: 100,
            lessLink: '<a class="hide-content" href="#">{{ 'SetInvisible'|get_lang|escape('js') }}</a>',
            moreLink: '<a class="read-more" href="#">{{ 'ReadMore'|get_lang|escape('js') }}</a>',
            collapsedHeight: 500,
            heightMargin: 100
        });
        $('.open-more').readmore({
            speed: 100,
            lessLink: '<a class="hide-content" href="#">{{ 'SetInvisible'|get_lang|escape('js') }}</a>',
            moreLink: '<a class="read-more" href="#">{{ 'ReadMore'|get_lang|escape('js') }}</a>',
            collapsedHeight: 90,
            heightMargin: 20
        });
    });
</script>
