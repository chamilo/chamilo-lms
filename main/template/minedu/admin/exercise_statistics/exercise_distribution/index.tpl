{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <script>
        function updateValues(id, distributionId, categoryId) {
            $.ajax({
                async:false,
                url: "{{ _p.web_ajax }}exercise.ajax.php?a=get_modificator",
                data: "distributionId="+distributionId+"&categoryId="+categoryId+"&cId={{ cId }}&sessionId={{ sessionId }}&exerciseId={{ exerciseId }}",
                success: function(modificator) {
                    var inputId = id.replace('button', 'input');
                    $("#"+inputId).attr('value', modificator);
                    var finId = id.replace('button', 'fin');
                    var origId = id.replace('button', 'orig');
                    var orig = $("#"+origId).text();
                    var result = parseFloat(orig) + parseFloat(modificator);
                    $("#"+finId).html(result.toFixed(2));
                }
            });
        }

        $(function () {
            $('.edit').click(function() {
                var id = $(this).attr('id');
                var newId = id.replace('button', 'form');

                var distributionId = newId.split('_')[1];
                var categoryId = newId.split('_')[2];

                result = $("#"+newId).toggle();

                if ($("#"+newId).css('display') == 'none') {
                    $("#"+id).removeClass('btn-danger');
                    $("#"+id).html('+');
                } else {
                    $("#"+id).addClass('btn-danger');
                    $("#"+id).html('-');
                }

                updateValues(id, distributionId, categoryId);
            });

            $('.save_button').click(function() {
                var id = $(this).attr('id');
                var newId = id.replace('save', 'input');
                var value = $("#"+newId).attr('value');
                var distributionId = newId.split('_')[1];
                var categoryId = newId.split('_')[2];

                $.ajax({
                    url: "{{ _p.web_ajax }}exercise.ajax.php?a=save_modificator",
                    data: "distributionId="+distributionId+"&categoryId="+categoryId+"&value="+value+"&cId={{ cId }}&sessionId={{ sessionId }}&exerciseId={{ exerciseId }}",
                    success: function(result) {
                        var resultId = id.replace('save', 'result');
                        if (result == 1) {
                            $("#"+resultId).html('{{ 'Saved' | trans }}');
                        } else {
                            $("#"+resultId).html('{{ 'Error' | trans }}');
                        }
                        var newId = id.replace('save', 'button');
                        updateValues(newId, distributionId, categoryId);
                    }
                });
            });
        });
    </script>

    <table class="table">
        <tr>
            <td>
            </td>
            {% for distribution in distributions %}
                <td>
                {{ distribution.distribution.title }} (# intentos {{ results[distribution.distribution.id].counter }} )
                </td>
            {% endfor %}
        </tr>

        {% for category in global_categories %}
            <tr>
                <td>
                    {{ category.title }}
                </td>
                {% for distribution in distributions %}
                    <td>
                        {% if results[distribution.distribution.id] and results[distribution.distribution.id][category.id] %}
                            {% set average = results[distribution.distribution.id][category.id].result / results[distribution.distribution.id].counter %}
                            {{ average|number_format(2) }} ({{ results[distribution.distribution.id][category.id].result }})

                            {% set id = distribution.distribution.id ~  "_" ~ category.id %}

                            <a id="button_{{ id }}" class="btn edit">+</a>

                            <div id = "form_{{ id }}" style ="display:none">
                                <table>
                                    <tr>
                                        <td>
                                            Ori: <span id="orig_{{ id }}">{{ average|number_format(2) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Mod:
                                            <span id="modificator_{{ id }}"></span>

                                            <input
                                                type="text"
                                                value=""
                                                id="input_{{ id }}"
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Fin:  <span id="fin_{{ id }}"></span>
                                        </td>
                                    </tr>
                                </table>
                                <a class="btn btn-success save_button" href="#" id="save_{{ id }}" >Guardar</a>
                                <span id="result_{{ id }}" ></span>
                            </div>

                        {% endif %}
                        </td>
                {% endfor %}
            </tr>
        {% endfor %}
        <tr>
            <td>
                Total
            </td>
            {% for distribution in distributions %}
                <td>
                    {{ results[distribution.distribution.id].total }}
                </td>
            {% endfor %}
        </tr>

    </table>
{% endblock %}
