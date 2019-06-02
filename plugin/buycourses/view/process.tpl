<div class="actions">
    <a href="{{ _p.web }}main/auth/courses.php" title="{{ "Back"|get_lang }}">
        <img src="{{ "back.png"|icon(32) }}" width="32" height="32" alt="{{ "Back"|get_lang }}"
             title="{{ "Back"|get_lang }}"/>
    </a>
</div>
<div class="page-header">
    <h3>{{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default panel-box-buy">
            <div class="panel-body">
                <div class="buy-info">
                    {% if buying_course %}
                    <div class="row">
                        <div class="col-md-3">
                            <a class="ajax" data-title="{{ course.title }}"
                               href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}">
                                <img alt="{{ course.title }}" class="img-rounded img-responsive"
                                     src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}">
                            </a>
                            {% if course.tax_enable %}
                            <div class="price-details-tax">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin')}} :
                                {{ course.currency == 'BRL' ? 'R$' : course.currency }} {{ course.price_without_tax }}
                                <br>
                                {{ course.tax_name }} ({{ course.tax_perc }}%):
                                {{ course.currency == 'BRL' ? 'R$' : course.currency }} {{ course.tax_amount }}
                            </div>
                            {% endif %}
                            <div class="price">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin')}} :
                                {{ course.currency == 'BRL' ? 'R$' : course.currency }} {{ course.price }}
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="buy-item">
                                <h3 class="title">
                                    <a class="ajax" data-title="{{ course.title }}"
                                       href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}">
                                        {{ course.title }}
                                    </a>
                                </h3>
                                {% if course.description %}
                                <div class="description">
                                    {{ course.description }}
                                </div>
                                {% endif %}
                                <div class="coaches">
                                    <p>
                                        {{ 'Teachers'|get_plugin_lang('BuyCoursesPlugin')}} :
                                        {% for teacher in course.teachers %}
                                        <em class="fa fa-user" aria-hidden="true"></em>
                                        <a href="{{ _p.web }}main/social/profile.php?u={{ teacher.id }}"
                                           class="teacher-item"> {{ teacher.name }}</a>,
                                        {% endfor %}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    {% elseif buying_session %}
                    <div class="row">
                        <div class="col-md-3">
                            <img alt="{{ session.name }}" class="img-rounded img-responsive""
                            src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                            {% if session.tax_enable %}
                            <div class="price-details-tax">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin')}} :
                                {{ session.currency == 'BRL' ? 'R$' : session.currency }} {{ session.price_without_tax }}
                                <br>
                                {{ session.tax_name }} ({{ session.tax_perc }}%):
                                {{ session.currency == 'BRL' ? 'R$' : session.currency }} {{ session.tax_amount }}
                            </div>
                            {% endif %}
                            <div class="price">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin')}} :
                                {{ session.currency == 'BRL' ? 'R$' : session.currency }} {{ session.price }}
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="buy-item">
                                <h3 class="title">{{ session.name }}</h3>
                                {% if session.description %}
                                <div class="description">
                                    {{ session.description }}
                                </div>
                                {% endif %}
                                <div class="date">
                                    <em class="fa fa-calendar" aria-hidden="true"></em> {{ session.dates.display }}
                                </div>
                                <div class="coaches">
                                    {% for course in session.courses %}
                                    <p class="course"><em class="fa fa-book" aria-hidden="true"></em> {{ course.title }}
                                    </p>
                                    <p>
                                        {{ 'Teachers'|get_plugin_lang('BuyCoursesPlugin')}} :
                                        {% if course.coaches|length %}
                                        {% for coach in course.coaches %}
                                        <em class="fa fa-user" aria-hidden="true"></em>
                                        <a href="{{ _p.web }}main/social/profile.php?u={{ coach.id }}"
                                           class="teacher-item"> {{ coach.name }}</a>,
                                        {% endfor %}
                                        {% endif %}
                                    </p>
                                    {% endfor %}
                                </div>
                            </div>
                        </div>
                    </div>
                    {% endif %}
                </div>
                <div class="buy-summary">
                    <h3>{{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
                    {{ form }}
                </div>
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
