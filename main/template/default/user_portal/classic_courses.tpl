<!-- view classic list special course -->
<div class="classic-courses">
    {% for item in special_courses %}
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
                                {{ 'klipper.png' | img(22, 'CourseAutoRegister'|get_lang ) }}
                            {% endif %}
                        </h4>
                        <div class="course-items-session">
                            {{ 'teacher.png' | img(16, 'Professor'|get_lang ) }}
                            <ul class="teachers">
                                {% for teacher in item.teachers %}
                                <li>
                                    {% set counter = counter + 1 %}
                                    {% if counter > 1 %} | {% endif %}
                                    <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                        {{ teacher.firstname }} {{ teacher.lastname }}
                                    </a>
                                </li>
                                {% endfor %}
                            </ul>  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endfor %}
</div>
<!-- view classic list course -->
<div class="classic-courses">
    <!-- courses in category -->
    {% for category in courses.in_category %}
    <div class="panel panel-default">
        <div id="category-{{ category.id_category }}" class="panel-heading">
            {{ category.title_category }}
        </div>
        <div class="panel-body">
            {% for item in category.courses %}
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
                            {% endif %}
                        </h4>
                        <div class="course-items-session">
                            {{ 'teacher.png' | img(16, 'Professor'|get_lang ) }}
                            <ul class="teachers">
                                {% for teacher in item.teachers %}
                                <li>
                                    {% set counter = counter + 1 %}
                                    {% if counter > 1 %} | {% endif %}
                                    <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                        {{ teacher.firstname }} {{ teacher.lastname }}
                                    </a>
                                </li>
                                {% endfor %}
                            </ul>  
                        </div>
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>
    {% endfor %}
    <!-- end courses in category -->
    <!-- courses with out categories -->
    {% for item in courses.not_category %}
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
                            {% endif %}
                        </h4>
                        <div class="course-items-session">
                            {{ 'teacher.png' | img(16, 'Professor'|get_lang ) }}
                            <ul class="teachers">
                                {% for teacher in item.teachers %}
                                <li>
                                    {% set counter = counter + 1 %}
                                    {% if counter > 1 %} | {% endif %}
                                    <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                        {{ teacher.firstname }} {{ teacher.lastname }}
                                    </a>
                                </li>
                                {% endfor %}
                            </ul>  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endfor %}
    <!-- end courses with out categories -->
</div>
