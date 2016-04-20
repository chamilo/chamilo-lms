{% for item in hot_courses %}
{% if item.title %}
<div class="col-md-4 col-sm-4 col-xs-6">
        <div class="items">
            <div class="image">
                <img src="{{ item.course_image_large }}" class="img-responsive">
                {% if item.categoryName != '' %}
                <span class="category">{{ item.categoryName }}</span>
                <div class="cribbon"></div>
                {% endif %}
                <div class="black_shadow">
                    <div class="author-card">  
                    {% for teacher in item.teachers %}
                        {% set counter = counter + 1 %}
                        {% if counter <= 3 %}
                        <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                            <img src="{{ teacher.avatar }}"/>
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
            </div>
            <div class="description">
                <h4 class="title">
                        <a title="{{ item.title}}" href="{{ item.course_public_url }}">{{ item.title}}</a>
                </h4>
                <div class="ranking">
                    {{ item.rating_html }}
                </div>
                <div class="toolbar">
                    <div class="btn-group" role="group">
                        {{ item.description_button }}
                        {{ item.register_button }}
                        {{ item.unsubscribe_button }}
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endif %}
{% endfor %}
