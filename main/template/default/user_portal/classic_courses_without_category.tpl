{% if not courses is empty %}
    <div class="classic-courses">
    {% for item in courses %}
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-2">
                        <a class="thumbnail">
                            {% if item.thumbnails != '' %}
                                <img src="{{ item.thumbnails }}" title="{{ item.title }}" alt="{{ item.title }}"/>
                            {% else %}
                                {{ 'blackboard.png' | img(48, item.title ) }}
                            {% endif %}
                        </a>
                    </div>
                    <div class="col-md-10">
                        {% if item.edit_actions != '' %}
                            <div class="pull-right">
                                {% if item.document == '' %}
                                    <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </a>
                                {% else %}
                                    <div class="btn-group" role="group">
                                        <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                        </a>
                                        {{ item.document }}
                                    </div>
                                {% endif %}
                            </div>
                        {% endif %}
                        <h4 class="course-items-title">
                            {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') %}
                                {{ item.title }} {{ item.code_course }}
                            {% else %}
                                <a href="{{ item.link }}">
                                    {{ item.title }} {{ item.code_course }}
                                </a>
                                {{ item.notifications }}
                                {% if item.is_special_course %}
                                    {{ 'klipper.png' | img(22, 'CourseAutoRegister'|get_lang ) }}
                                {% endif %}
                            {% endif %}
                        </h4>
                        <div class="course-items-session">
                            <div class="list-teachers">
                                {{ 'teacher.png' | img(16, 'Professor'|get_lang ) }}
                                {% for teacher in item.teachers %}
                                    {% set counter = counter + 1 %}
                                    {% if counter > 1 %} | {% endif %}
                                    <a href="{{ teacher.url }}" class="ajax"
                                       data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                        {{ teacher.firstname }} {{ teacher.lastname }}
                                    </a>
                                {% endfor %}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endfor %}
    </div>
{% endif %}