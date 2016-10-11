{% for item in hot_courses %}
    {% if item.title %}
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="items items-hotcourse">
                <div class="image">
                    <img src="{{ item.course_image_large }}" class="img-responsive" alt="{{ item.title }}">
                    {% if item.categoryName != '' %}
                        <span class="category">{{ item.categoryName }}</span>
                        <div class="cribbon"></div>
                    {% endif %}
                    <div class="black-shadow">
                        <div class="author-card">
                        {% for teacher in item.teachers %}
                            {% set counter = counter + 1 %}
                            {% if counter <= 3 %}
                                <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                    <img src="{{ teacher.avatar }}" alt="{{ teacher.firstname }} {{ teacher.lastname }}"/>
                                </a>
                                <div class="teachers-details">
                                     <h5>
                                        <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                            {{ teacher.firstname }} {{ teacher.lastname }}
                                        </a>
                                     </h5>
                                </div>
                            {% endif %}
                        {% endfor %}
                        </div>
                    </div>
                    <div class="user-actions">{{ item.description_button }}</div>
                </div>
                <div class="description">
                    <h4 class="title">
                        <a title="{{ item.title}}" href="{{ item.course_public_url }}">{{ item.title}}</a>
                    </h4>
                    <div class="ranking">
                        {{ item.rating_html }}
                    </div>
                    <div class="toolbar">
                        <div class="left">
                            {% if item.price %}
                                {{ item.price }}
                            {% endif %}
                        </div>
                        <div class="right">
                            <div class="btn-group" role="group">
                                {{ item.register_button }}
                                {{ item.unsubscribe_button }}
                                {{ item.already_register_as }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
