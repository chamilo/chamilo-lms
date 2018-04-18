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
                            <div class="price">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin')}} :
                                {{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }}
                            </div>
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
                                        <em class="fa-li fa fa-money" aria-hidden="true"></em>
                                        {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                                        : {{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }}
                                        / {{ service.duration_days == 0 ? 'NoLimit'|get_lang  : service.duration_days ~ ' ' ~ 'Days'|get_lang }}
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
    $(document).ready(function () {
        $("label").removeClass('control-label');
        $('.form_required').remove();
        $("small").remove();
        $("label[for=submit]").remove();
    });
</script>
