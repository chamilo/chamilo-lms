<!-- Inicia el slider -->

    <div id="slider-banner" class="carousel slide" data-ride="carousel">
        <!-- Indicators -->
        <ol class="carousel-indicators">
            {% for announcement in announcements %}

            <li data-target="#slider-banner" data-slide-to="{{ loop.index0 }}" {% if loop.index0 == 0 %} class="active" {% endif %}></li>

            {% endfor %}
        </ol>

        <!-- Wrapper for slides -->
        <div class="carousel-inner" role="listbox">
            {% for announcement in announcements %}
            <div class="item {% if loop.index0 == 0 %} active {% endif %}"">
                {{ announcement.content }}
                {% if announcement.readMore %}
                    <a href="{{ _p.web }}news_list.php?id={{ announcement.id }}">{{ "More" | get_lang }}</a>
                {% endif %}
            </div>
            {% endfor %}
        </div>
    </div>


<!-- fin del slider -->