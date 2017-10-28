{% for item in hot_courses %}
    {% if item.title %}
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="items items-hotcourse">
                <div class="image">
                    {% if item.is_registered %}
                        <a title="{{ item.title}}" href="{{ item.course_public_url }}">
                            <img src="{{ item.course_image_large }}" class="img-responsive" alt="{{ item.title }}">
                        </a>
                    {% else %}
                        <img src="{{ item.course_image_large }}" class="img-responsive" alt="{{ item.title }}">
                    {% endif %}
                    {% if item.categoryName != '' %}
                        <span class="category">{{ item.categoryName }}</span>
                        <div class="cribbon"></div>
                    {% endif %}
                    <div class="user-actions">{{ item.description_button }}</div>
                </div>
                <div class="description">
                    <div class="block-title">
                        {% if item.is_registered or _u.is_admin %}
                            <h4 class="title">
                                <a alt="{{ item.title }}" title="{{ item.title }}" href="{{ item.course_public_url }}">
                                    {{ item.title_cut}}
                                </a>
                            </h4>
                        {% else %}
                            <h4 class="title" title="{{ item.title }}">
                                {{ item.title_cut}}
                            </h4>
                        {% endif %}
                    </div>
                    <div class="ranking">
                        {{ item.rating_html }}
                    </div>
                    <div class="block-author">
                        {% if item.teachers | length > 6 %}
                            <a id="plist-{{ loop.index }}" data-trigger="focus" tabindex="0" role="button" class="btn btn-default panel_popover" data-toggle="popover" title="{{ 'CourseTeachers' | get_lang }}" data-html="true">
                                <em class="fa fa-graduation-cap" aria-hidden="true"></em>
                            </a>
                            <div id="popover-content-plist-{{ loop.index }}" class="hide">
                                {% for teacher in item.teachers %}
                                    <div class="popover-teacher">
                                        <a href="{{ teacher.url }}" class="ajax"
                                           data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                            <img src="{{ teacher.avatar }}" alt="{{ teacher.fullname }}"/>
                                        </a>
                                        <div class="teachers-details">
                                            <h5>
                                                <a href="{{ teacher.url }}" class="ajax"
                                                   data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                                    {{ teacher.firstname }} {{ teacher.lastname }}
                                                </a>
                                            </h5>
                                        </div>
                                    </div>
                                {% endfor %}
                            </div>
                        {% else %}
                            {% for teacher in item.teachers %}
                                {% if item.teachers | length <= 2 %}
                                    <a href="{{ teacher.url }}" class="ajax"
                                       data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                        <img src="{{ teacher.avatar }}" alt="{{ teacher.fullname }}"/>
                                    </a>
                                    <div class="teachers-details">
                                        <h5>
                                            <a href="{{ teacher.url }}" class="ajax"
                                               data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                                {{ teacher.firstname }} {{ teacher.lastname }}
                                            </a>
                                        </h5>
                                        <p>{{ 'Teacher' | get_lang }}</p>
                                    </div>
                                {% elseif item.teachers | length <= 6 %}
                                    <a href="{{ teacher.url }}" class="ajax"
                                       data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                        <img src="{{ teacher.avatar }}" alt="{{ teacher.fullname }}"/>
                                    </a>
                                {% endif %}
                            {% endfor %}
                        {% endif %}
                    </div>
                    <div class="toolbar row">
                        <div class="col-sm-4">
                            {% if item.price %}
                                {{ item.price }}
                            {% endif %}
                        </div>
                        <div class="col-sm-8">
                            <div class="btn-group" role="group">
                                {{ item.register_button }}
                                {{ item.unsubscribe_button }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
