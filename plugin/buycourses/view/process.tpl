<script type='text/javascript' src="../js/buycourses.js"></script>

<div class="row">
    <div class="col-md-7">
        <h3 class="page-header">{{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
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
            {% endif %}
        </div>
    </div>
    <div class="col-md-5">
        {{ form }}
    </div>
</div>
