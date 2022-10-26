<div id="about-course">
    <div id="course-info-top">
        <h2 class="session-title">{{ course.title }}</h2>
        {% if not 'course_about_teacher_name_hide'|api_get_configuration_value %}
            <div class="course-short">
                <ul>
                    <li class="author">{{ "Professors"|get_lang }}</li>
                    {%  for teacher in course.teachers %}
                        <li>{{ teacher.complete_name }} | </li>
                    {% endfor %}
                </ul>
            </div>
        {% endif %}
    </div>

    {% set course_video = '' %}
        {% for extra_field in course.extra_fields %}
        {% if extra_field.value.getField().getVariable() == 'video_url' %}
            {% set course_video = extra_field.value.getValue() %}
        {% endif %}
    {% endfor %}

    <div class="panel panel-default">
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-5">
                    {% if course_video %}
                    <div class="course-video">
                        <div class="embed-responsive embed-responsive-16by9">
                            {{ essence.replace(course_video) }}
                        </div>
                    </div>
                    {% else %}
                    <div class="course-image">
                        <img src="{{ course.image }}" class="img-responsive" />
                    </div>
                    {% endif %}
                    {% if not 'hide_social_media_links'|api_get_configuration_value %}
                    <div class="share-social-media">
                        <ul class="sharing-buttons">
                            <li>
                                {{ "ShareWithYourFriends"|get_lang }}
                            </li>
                            <li>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ url }}"
                                   target="_blank" class="btn btn-facebook btn-inverse btn-xs">
                                    <em class="fa fa-facebook"></em> Facebook
                                </a>
                            </li>
                            <li>
                                <a href="https://twitter.com/home?{{ {'status': course.title ~ ' ' ~ url }|url_encode }}"
                                   target="_blank" class="btn btn-twitter btn-inverse btn-xs">
                                    <em class="fa fa-twitter"></em> Twitter
                                </a>
                            </li>
                            <li>
                                <a href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': url , 'title': course.title }|url_encode }}"
                                   target="_blank" class="btn btn-linkedin btn-inverse btn-xs">
                                    <em class="fa fa-linkedin"></em> Linkedin
                                </a>
                            </li>
                        </ul>
                    </div>
                    {% endif %}
                </div>
                <div class="col-sm-7">
                    <div class="course-description">
                        {{ course.description | remove_xss }}
                    </div>
                </div>
            </div>
            {% if course.tags %}
                <div class="panel-tags">
                    <ul class="list-inline course-tags">
                        <li>{{ 'Tags'|get_lang }} :</li>
                        {% for tag in course.tags %}
                            <li class="tag-value">
                                <span>{{ tag.getTag | remove_xss }}</span>
                            </li>
                        {% endfor %}
                    </ul>
                </div>
            {% endif %}
        </div>
    </div>
    <section id="course-info-bottom" class="course">
        <div class="row">
            <div class="col-sm-8">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="sub-title">{{ "CourseInformation"|get_lang }}</h3>
                        <div class="course-information">
                            {% for topic in course.syllabus %}
                                {% if topic.content != '' %}
                                    <div class="topics">
                                        <h4 class="title-info">
                                            <em class="fa fa-book"></em> {{ topic.title | remove_xss }}
                                        </h4>
                                        <div class="content-info">
                                            {{ topic.content | remove_xss }}
                                        </div>
                                    </div>
                                {% endif %}
                            {% endfor %}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-body">
                        {% if allow_subscribe == true %}
                            {% if is_premium == false %}
                                <div class="session-subscribe">
                                    {# public course (open world) #}
                                    {% if 3 == course.visibility %}
                                    <a href="{{ _p.web }}courses/{{ course.code }}/index.php?id_session=0"
                                       class="btn btn-lg btn-success btn-block">
                                        {{ 'CourseHomepage'|get_lang }}
                                    </a>
                                    {% elseif _u.logged == 0 %}
                                    {% if 'allow_registration'|api_get_setting != 'false' %}
                                    <a
                                            href="{{ _p.web_main ~ 'auth/inscription.php' ~ redirect_to_session }}"
                                            class="btn btn-success btn-block btn-lg">
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                        {{ 'SignUp'|get_lang }}
                                    </a>
                                    {% endif %}
                                    {% elseif course.subscription %}
                                    <a href="{{ _p.web }}courses/{{ course.code }}/index.php?id_session=0"
                                       class="btn btn-lg btn-success btn-block">
                                        {{ 'CourseHomepage'|get_lang }}
                                    </a>
                                    {% else %}
                                    <a
                                            href="{{ _p.web }}courses/{{ course.code }}/index.php?action=subscribe&sec_token={{ token }}"
                                            class="btn btn-lg btn-success btn-block">
                                        {{ 'Subscribe'|get_lang }}
                                    </a>
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
                                    <div class="buy-box">
                                        <a
                                                href="{{ _p.web }}plugin/buycourses/src/process.php?i={{ is_premium.product_id }}&t={{ is_premium.product_type }}"
                                                class="btn btn-lg btn-primary btn-block">
                                            {{ 'BuyNow'|get_lang }}
                                        </a>
                                    </div>
                                </div>
                            {% endif %}
                        {% else %}
                            <div class="session-subscribe">
                                <button class="btn btn-lg btn-default btn-block" disabled>
                                    {{ 'Subscribe'|get_lang }}
                                </button>
                            </div>
                        {% endif %}
                        {% if has_requirements %}
                            <div class="session-requirements">
                                <h5>{{ 'RequiredCourses'|get_lang }}</h5>
                                <p>
                                    {{ subscribe_button }}
                                </p>
                                {% for sequence in sequences %}
                                {% if sequence.requirements %}
                                <p>
                                    {{ sequence.name }} :
                                    {% for requirement in sequence.requirements %}
                                    <a href="{{ _p.web ~ 'course/' ~ requirement.getId ~ '/about/' }}">
                                        {{ requirement.title | remove_xss }}
                                    </a>
                                    {% endfor %}
                                </p>
                                {% endif %}
                                {% endfor %}
                            </div>
                        {% endif %}
                    </div>
                </div>
                {% if course.teachers and not 'course_about_teacher_name_hide'|api_get_configuration_value %}
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="panel-teachers">
                                <h3 class="sub-title">{{ "Coaches"|get_lang }}</h3>
                            </div>
                            {%  for teacher in course.teachers %}
                            <div class="coach-information">
                                <div class="coach-header">
                                    <div class="coach-avatar">
                                        <img class="img-circle img-responsive"
                                             src="{{ teacher.image }}"
                                             alt="{{ teacher.complete_name }}"
                                        >
                                    </div>
                                    <div class="coach-title">
                                        <h4>{{ teacher.complete_name }}</h4>
                                        <p> {{ teacher.diploma | remove_xss }}</p>
                                    </div>
                                </div>
                                <div class="open-area  {{ course.teachers | length >= 2 ? 'open-more' : ' ' }}">
                                    {{ teacher.openarea | remove_xss }}
                                </div>
                            </div>
                            {% endfor %}
                        </div>
                    </div>
                {% endif %}
            </div>
        </div>
    </section>
</div>

<script>
    $(document).ready(function() {
        $('.course-information').readmore({
            speed: 100,
            lessLink: '<a class="hide-content" href="#">{{ 'SetInvisible' | get_lang }}</a>',
            moreLink: '<a class="read-more" href="#">{{ 'ReadMore' | get_lang }}</a>',
            collapsedHeight: 730,
            heightMargin: 100
        });
        $('.open-more').readmore({
            speed: 100,
            lessLink: '<a class="hide-content" href="#">{{ 'SetInvisible' | get_lang }}</a>',
            moreLink: '<a class="read-more" href="#">{{ 'ReadMore' | get_lang }}</a>',
            collapsedHeight: 90,
            heightMargin: 20
        });
    });
</script>
