<div class="actions">
    {% if item_type == 1 %}
        {% set back_url = _p.web_plugin ~ 'buycourses/src/course_catalog.php' %}
    {% elseif item_type == 2 %}
        {% set back_url = _p.web_plugin ~ 'buycourses/src/session_catalog.php' %}
    {% else %}
        {% set back_url = _p.web_plugin ~ 'buycourses/src/service_catalog.php' %}
    {% endif %}

    <a href="{{ back_url }}" title="{{ "Back"|get_lang }}">
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
                                        {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }} :
                                        {{ course.item.price_formatted }}
                                        <br>
                                        {{ course.tax_name }} ({{ course.item.tax_perc_show }}%):
                                        {{ course.item.tax_amount_formatted }}
                                    </div>
                                {% endif %}
                                <div class="price">
                                    {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }} :
                                    {{ course.item.total_price_formatted }}
                                </div>
                                {% if course.item.has_coupon %}
                                    <div class="price-details-tax">
                                        {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}:
                                        {{ course.item.discount_amount_formatted }}
                                    </div>
                                {% endif %}
                                <div class="coupon-question">
                                    {{ 'DoYouHaveACoupon'|get_plugin_lang('BuyCoursesPlugin') }}
                                </div>
                                <div class="coupon">
                                    {{ form_coupon }}
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

                                    {% if course.teachers %}
                                        <div class="coaches">
                                            <p>
                                                {{ 'Teachers'|get_plugin_lang('BuyCoursesPlugin') }} :
                                                {% for teacher in course.teachers %}
                                                    <em class="fa fa-user" aria-hidden="true"></em>
                                                    <a href="{{ _p.web }}main/social/profile.php?u={{ teacher.id }}"
                                                       class="teacher-item"> {{ teacher.name }}</a>,
                                                {% endfor %}
                                            </p>
                                        </div>
                                    {% endif %}
                                </div>
                            </div>
                        </div>
                    {% elseif buying_session %}
                        <div class="row">
                            <div class="col-md-3">
                                <img alt="{{ session.name }}" class="img-rounded img-responsive"
                                     src="{{ session.image ? session.image : 'session_default.png'|icon() }}">
                                {% if session.tax_enable %}
                                    <div class="price-details-tax">
                                        {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }} :
                                        {{ session.item.price_formatted }}
                                        <br>
                                        {{ session.tax_name }} ({{ session.item.tax_perc_show }}%):
                                        {{ session.item.tax_amount_formatted }}
                                    </div>
                                {% endif %}
                                <div class="price">
                                    {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }} :
                                    {{ session.item.total_price_formatted }}
                                </div>
                                {% if session.item.has_coupon %}
                                    <div class="price-details-tax">
                                        {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}:
                                        {{ session.item.discount_amount_formatted }}
                                    </div>
                                {% endif %}
                                <div class="coupon-question">
                                    {{ 'DoYouHaveACoupon'|get_plugin_lang('BuyCoursesPlugin') }}
                                </div>
                                <div class="coupon">
                                    {{ form_coupon }}
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
                                    <hr>
                                    <div class="coaches">
                                        {% for course in session.courses %}
                                            <p class="course">
                                                <em class="fa fa-book" aria-hidden="true"></em> {{ course.title }}
                                            </p>
                                            {% if course.coaches|length %}
                                                <p>
                                                    {{ 'Teachers'|get_plugin_lang('BuyCoursesPlugin') }} :

                                                    {% for coach in course.coaches %}
                                                        <em class="fa fa-user" aria-hidden="true"></em>
                                                        <a href="{{ _p.web }}main/social/profile.php?u={{ coach.id }}"
                                                           class="teacher-item">{{ coach.name }}</a>,
                                                    {% endfor %}
                                                </p>
                                            {% endif %}
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
    $(function () {
        $("label").removeClass('control-label');
        $('.form_required').remove();
        $("small").remove();
        $("label[for=submit]").remove();
    });
</script>
