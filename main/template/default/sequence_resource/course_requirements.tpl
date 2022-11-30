{% if 'requirements' == item_type %}
    <h3>{{ 'RequiredCourses'|get_lang }}</h3>
{% else %}
    <h3>{{ 'Dependencies'|get_lang }}</h3>
{% endif %}

{% for key, item in sequences %}
    {% if 'requirements' == item_type %}
        {% set courses = item.requirements %}
    {% else %}
        {% set courses = item.dependents %}
    {% endif %}

    <h4>{{ item.name }}</h4>
    <div id="parents">
        {% for c_id, course in courses %}
            <div class="parent">
                <div class="big-icon">
                    <img src="{{ 'item-sequence.png'|icon(48) }}" width="48" height="48">
                    <p class="sequence-course">
                        {% if current_requirement_is_completed %}
                            <a href="{{ _p.web_course ~ course.code ~ '/index.php?' ~ { 'id_session': _c.session_id }|url_encode }}">{{ course.name }}</a>
                        {% else %}
                            {{ course.name }}
                        {% endif %}
                    </p>

                    {% if _u.logged %}
                        <span class="label {{ course.status ? 'label-success' : 'label-danger' }}">
                            {% if course.status %}
                                <em class="fa fa-check"></em> {{ 'Complete'|get_lang }}
                            {% else %}
                                <em class="fa fa-exclamation-triangle"></em> {{ 'Incomplete'|get_lang }}
                            {% endif %}
                        </span>
                    {% endif %}
                </div>
            </div>

            {% if loop.index != courses|length %}
                <em class="fa fa-plus fa-3x sequence-plus-icon"></em>
            {% endif %}
        {% endfor %}
    </div>

    <script>
        var url = '{{ _p.web_ajax }}sequence.ajax.php?type={{ sequence_type }}';
        var sequenceId = '{{ key }}';
        $(function() {
            $.ajax({
                url: url + '&a=graph&sequence_id=' + sequenceId,
                success: function (data) {
                    $('#show_graph').append(data);
                }
            });
        });
    </script>
    <div id="show_graph">{{ graph }}</div>
{% endfor %}

{% if allow_subscription %}
    <hr>
    <p>{{ subscribe_button }}</p>
{% endif %}
