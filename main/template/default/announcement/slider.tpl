<h2 class="page-header">{{ "SystemAnnouncements" | get_lang }}</h2>

<div id="announcements-slider" class="carousel slide" data-ride="carousel" style="height:300px;">
    <ol class="carousel-indicators">
        {% for announcement in announcements %}
            <li data-target="#announcements-slider" data-slide-to="{{ loop.index0 }}" {% if loop.index0 == 0 %} class="active" {% endif %}></li>
        {% endfor %}
    </ol>

    <div class="carousel-inner" role="listbox">
        {% for announcement in announcements %}
            <div class="item {% if loop.index0 == 0 %} active {% endif %}" style="height:300px;">
                <h5>{{ announcement.title }}</h5>
                {{ announcement.content }}
                {% if announcement.readMore %}
                    <a href="{{ _p.web }}news_list.php?id={{ announcement.id }}">{{ "More" | get_lang }}</a>
                {% endif %}
                <div class="carousel-caption">
                </div>
            </div>
        {% endfor %}
    </div>

    <a class="left carousel-control" href="#announcements-slider" role="button" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <span class="sr-only">{{ "Previous" | get_lang }}</span>
    </a>
    <a class="right carousel-control" href="#announcements-slider" role="button" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        <span class="sr-only">{{ "Next" | get_lang }}</span>
    </a>
</div>
