<style>
    .announcement_short {
        height: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div id="carousel-announcement" class="carousel slide" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
      {% for announcement in announcements %}
        <li data-target="#carousel-announcement" data-slide-to="{{ loop.index0 }}" {% if loop.index0 == 0 %} class="active" {% endif %}></li>
      {% endfor %}
    </ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner" role="listbox">
    {% for announcement in announcements %}
        <div class="item {% if loop.index0 == 0 %} active {% endif %}">
            <div class="carousel-caption">
                {{ announcement.title }}
            </div>
            <div class="carousel-content">
                {% if announcement.readMore %}
                    <div class="block-text">
                        <div class="announcement_short">
                        {{ announcement.content }}
                        </div>
                        <a href="{{ _p.web }}news_list.php?id={{ announcement.id }}" class="link-more">{{ "More" | get_lang }}</a>
                    </div>
                {% else %}
                    <div class="block-image">
                        {{ announcement.content }}
                    </div>
              {% endif %}
            </div>
        </div>
    {% endfor %}
    </div>

    <!-- Controls -->
    <a class="left carousel-control" href="#carousel-announcement" role="button" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="right carousel-control" href="#carousel-announcement" role="button" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
</div>