<script type='text/javascript' src="../js/buycourses.js"></script>

<div class="row">
    <div class="col-md-5 panel panel-default buycourse-panel-default">
        <h3 class="panel-heading">{{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        <legend></legend>
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
            {% elseif buying_service %}
                <div class="col-sm-12 col-md-12 col-xs-12">
                    <a href='{{ _p.web }}service/{{ service.id }}'>
                        <img alt="{{ service.name }}" class="img-responsive" src="{{ _p.web }}plugin/buycourses/uploads/services/images/{{ service.image }}">
                    </a>
                </div>
                <div class="col-sm-12 col-md-12 col-xs-12">
                    <h3>
                        <a href='{{ _p.web }}service/{{ service.id }}'>{{ service.name }}</a>
                    </h3>
                    <ul class="list-unstyled">
                        {% if service.applies_to == 0 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'None' | get_lang }}</li>
                        {% elseif service.applies_to == 1 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'User' | get_lang }}</li>
                        {% elseif service.applies_to == 2 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Course' | get_lang }}</li>
                        {% elseif service.applies_to == 3 %}
                            <li><em class="fa fa-hand-o-right"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Session' | get_lang }}</li>
                        {% endif %}
                        <li><em class="fa fa-money"></em> {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }} : {{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }} / {{ service.duration_days == 0 ? 'NoLimit' | get_lang  : service.duration_days ~ ' ' ~ 'Days' | get_lang }} </li>
                        <li><em class="fa fa-user"></em> {{ service.owner_name }}</li>
                        <li><em class="fa fa-align-justify"></em> {{ service.description }}</li>
                    </ul>
                    <p id="n-price" class="lead text-right" style="color: white;"><span class="label label-primary">{{ service.currency == 'BRL' ? 'R$' : service.currency }} {{ service.price }}</span></p>
                    <p id="s-price" class="lead text-right"></p>
                </div>
                <script>
                    $(document).ready(function() {
                        $("input[name=info_select_trick]").prop('disabled', true);
                        $("input[name=payment_type]").attr('hidden', true);
                        $("input[name=payment_type]").attr('checked', true);
                        $("#paypal-icon").html(' <em class="fa fa-check text-success fa-2x" aria-hidden="true"></em>');
                        $("input[name=payment_type]").click(function () {
                            $("#paypal-icon").html(' <em class="fa fa-check text-success fa-2x" aria-hidden="true"></em>');
                        })
                        $("label").removeClass('control-label');
                        $('.form_required').remove();
                        $("small").remove();
                        $("label[for=submit]").remove();
                        $("input[name=enable_trial]").attr('checked', true);

                        $("#code-checker").click(function () {
                            var code = $("#store_code").val();
                            $.ajax({
                                beforeSend: function() {
                                    $("#code-checker").html('<em class="fa fa-refresh fa-spin"></em> {{ 'Loading' | get_lang }}');
                                },
                                type: "POST",
                                url: "{{ _p.web_plugin ~ 'buycourses/src/buycourses.ajax.php?a=verify_discount_code' }}",
                                data : { store_code: code},
                                success: function(response) {
                                    $("#code-checker").html('{{ 'Check' | get_plugin_lang('BuyCoursesPlugin') }}');
                                    if (response.msg == 'true') {
                                        var store_name = response.store_name;
                                        var description = response.description;
                                        var type = response.type;
                                        var parent = '';
                                        if (response.parent) {
                                            parent = '<div class="form-group"><label for="code_parent" class="col-sm-6">{{ 'Parent' | get_plugin_lang('BuyCoursesPlugin') }}</label><div class="col-sm-6">' + response.parent + '</div></div>'
                                        }
                                        $("input[name=info_select]").val(store_name);
                                        $("#info_select_trick").val(store_name);
                                        $("#code-verificator-text").html('<p style="color: green">{{ 'ValidCode' | get_plugin_lang('BuyCoursesPlugin') }}</p>');
                                        $("#code-verificator-info").html('' +
                                            '<div class="form-group"><label for="code_description" class="col-sm-6">{{ 'Description' | get_plugin_lang('BuyCoursesPlugin') }}</label><div class="col-sm-6">' + description + '</div></div>' +
                                            '<div class="form-group"><label for="code_type" class="col-sm-6">{{ 'Type' | get_plugin_lang('BuyCoursesPlugin') }}</label><div class="col-sm-6">' + type + '</div></div>' + parent +
                                            '');
                                        var price = {{ service.price }};
                                        var show = response.discount;
                                        var discount = price * (response.discount / 100);
                                        var total = price - discount;
                                        $("#n-price").css('text-decoration', 'line-through');
                                        $("#s-price").html('<b>Desconto especial de ' + show + '%</b> <span class="label label-success">{{ service.currency == 'BRL' ? 'R$' : service.currency }} ' + total.toFixed(2) + '</span>');
                                    } else if (response.msg == 'false') {
                                        $("#code-verificator-text").html('<p style="color: red">{{ 'CodeDoesntExist' | get_plugin_lang('BuyCoursesPlugin') }}</p>');
                                        $("input[name=info_select]").val($("#store_code").val());
                                        $("#info_select_trick").val($("#store_code").val());
                                    }
                                }
                            });
                        });
                    });
                </script>
            {% endif %}
        </div>
    </div>
    <div class="col-md-1">
    </div>
    <div class="col-md-6 panel panel-default buycourse-panel-default">
        <h3 class="panel-heading">{{ 'PaymentMethods' | get_plugin_lang('BuyCoursesPlugin') }}</h3>
        {{ form }}
    </div>
</div>
