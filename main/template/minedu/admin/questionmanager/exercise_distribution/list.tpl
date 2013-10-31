{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <script>
        $(function () {
            $('#close_button').click(function(){
                $('#question_info').hide();
            });

            $('.loadQuestion').click(function(){
                var questionId = $(this).text();
                $.ajax({
                    dataType: "json",
                    url: "{{ _p.web_ajax }}exercise.ajax.php?a=get_question_info&cidReq={{ course.code }}",
                    data: "questionId="+questionId+"",
                    success: function(questionInfo) {
                        if (questionInfo == 0) {
                            $('#question_title').html('Pregunta no encontrada');
                            $('#question_category_list').html(' ');
                            $('#question_description').html(' ');
                            $('#question_info').show();
                        } else {
                            $('#question_title').html(questionInfo.question);
                            $('#question_description').html(questionInfo.description);
                            $('#question_category_list').html(questionInfo.category_list);
                            $('#question_info').show();
                        }
                    }
                });
            });
        });
    </script>

    <div class="actions">
        <a href="{{ exerciseUrl }}">
            {{ 'Back' |trans }}
        </a>
        <a href="{{ url('exercise_distribution.controller:addManyDistributionAction', {'exerciseId' : exerciseId, 'cidReq':course.code, 'id_session' : course_session.id }) }}">
            {{ 'Add' |trans }}
        </a>
        <a href="{{ url('exercise_distribution.controller:showStatsAction', {'exerciseId' : exerciseId, 'cidReq':course.code, 'id_session' : course_session.id }) }}">
            {{ 'Stats' |trans }}
        </a>
    </div>
    <span id="question_info" style="display:none">
        <div class="well">
        <h3><span id="question_title"></span></h3>
        <span id="question_description"></span>
        <span id="question_category_list"></span>
        <span id="close_button" ><a href="#" class="btn">Cerrar</a></span>
        </div>
    </span>

    <table class="table">

        <th>{{ 'Name' | trans }}</th>
        <th>{{ 'Selected distribution' | trans }}</th>
        <th>{{ 'Questions' | trans }}</th>
        <th>{{ 'Actions' | trans }}</th>

        {% for item in items %}
            <tr>
                <td>
                    <a href="{{ url('exercise_distribution.controller:readAction', {
                        'exerciseId' : exerciseId,
                        'id' : item.id ,
                        'cidReq':course.code,
                        'id_session' : course_session.id
                    }) }}">
                        {{ item.title }}
                    </a>
                </td>
                <td>
                    {% if item.id in selected_distribution_id_list %}
                        <i class="icon-check"></i>
                    {% else %}
                        <i class="icon-check-empty"></i>
                    {% endif %}
                </td>
                <td>
                    {% if item.dataTracking  %}
                        <a class="loadQuestion btn">{{ item.dataTracking |replace({',': '</a> <a class="loadQuestion btn">'}) }}</a>
                    {% endif %}
                </td>
                <td>
                    {% if item.active == 1 %}
                        <a class="btn" href="{{ url('exercise_distribution.controller:toggleVisibilityAction',{
                            'exerciseId' : exerciseId,
                            'id' : item.id,
                            'cidReq':course.code,
                            'id_session' : course_session.id })
                        }}">
                            <i class="icon-eye-open"></i>
                        </a>
                    {% else %}
                        <a class="btn" href="{{ url('exercise_distribution.controller:toggleVisibilityAction', {
                            'exerciseId' : exerciseId,
                            'id': item.id,
                            'cidReq':course.code,
                            'id_session' : course_session.id
                            })
                        }}">
                        <i class="icon-eye-close"></i>
                        </a>
                    {% endif %}

                    <a class="btn" href="{{ url('exercise_distribution.controller:toggleActivationAction', {
                        'exerciseId' : exerciseId,
                        'id': item.id,
                        'cidReq':course.code,
                        'id_session' : course_session.id
                    }) }}">
                        {{ 'Change activation' |trans }}
                    </a>

                    <a class="btn" href="{{ url('exercise_distribution.controller:showStatsAction', {
                        'exerciseId' : exerciseId,
                        'id': item.id,
                        'cidReq':course.code,
                        'id_session': course_session.id
                    })
                    }}">
                        {{ 'Stats' |trans }}
                    </a>

                    <a class="btn btn-danger" href="{{ url('exercise_distribution.controller:deleteDistributionAction', {
                        'exerciseId' : exerciseId,
                        'id': item.id,
                        'cidReq': course.code,
                        'id_session' : course_session.id
                    }) }}">
                        {{ 'Delete' |trans }}
                    </a>
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}
