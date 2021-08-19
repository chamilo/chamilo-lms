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
                {% if buying_service %}
                    <div class="row">
                        <div class="col-md-3">
                            <a href='{{ _p.web }}service/{{ service.id }}'>
                                <img alt="{{ service.name }}" class="img-rounded img-responsive"
                                     src="{{ service.image ? service.image : 'session_default.png'|icon() }}">
                            </a>
                            {% if service.tax_enable %}
                                <div class="price-details-tax">
                                    {{ 'Price'|get_plugin_lang('BuyCoursesPlugin')}} :
                                    {{ service.price_formatted }}
                                    <br>
                                    {{ service.tax_name }} ({{ service.tax_perc_show }}%):
                                    {{ service.tax_amount_formatted }}
                                </div>
                                <div class="price">
                                    {{ 'Total'|get_plugin_lang('BuyCoursesPlugin')}} :
                                    {{ service.total_price_formatted }}
                                </div>
                                {% if service.has_coupon %}
                                    <div class="price-details-tax">
                                        {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}:
                                        {{ service.discount_amount_formatted }}
                                    </div>
                                {% endif %}
                                <div class="coupon-question">
                                    {{ 'DoYouHaveACoupon'|get_plugin_lang('BuyCoursesPlugin') }}
                                </div>
                                <div class="coupon">
                                    {{ form_coupon }}
                                </div>
                            {% else %}
                            <div class="price">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin')}} :
                                 {{ service.total_price_formatted }}
                            </div>
                            {% if service.has_coupon %}
                                <div class="price-details-tax">
                                    {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}:
                                    {{ service.discount_amount_formatted }}
                                </div>
                            {% endif %}
                            <div class="coupon-question">
                                {{ 'DoYouHaveACoupon'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="coupon">
                                {{ form_coupon }}
                            </div>
                            {% endif %}

                        </div>
                        <div class="col-md-9">
                            <div class="buy-item">
                                <h3 class="title">
                                    <a href='{{ _p.web }}service/{{ service.id }}'>{{ service.name }}</a>
                                </h3>
                                {% if service.description %}
                                <div class="description">
                                    {{ service.description }}
                                </div>
                                {% endif %}
                                <ul class="fa-ul list-description">
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
                                        <em class="fa-li fa fa-clock-o" aria-hidden="true"></em>
                                        {{ service.duration_days == 0 ? 'NoLimit'|get_lang  : service.duration_days ~ ' ' ~ 'Days'|get_lang }}
                                    </li>
                                    <li><em class="fa-li fa fa-user" aria-hidden="true"></em> {{ service.owner_name }}</li>

                                </ul>
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
