<h4 class="page-header">{{ "SystemAnnouncements" | get_lang }}</h4>

<div id="announcements-slider" class="carousel slide" data-ride="carousel" style="height:300px;">
    <ol class="carousel-indicators">
        {% for announcement in announcements %}
            <li data-target="#announcements-slider" data-slide-to="{{ loop.index0 }}" {% if loop.index0 == 0 %} class="active" {% endif %}></li>
        {% endfor %}
    </ol>

    <div class="carousel-inner" role="listbox">
        {% for announcement in announcements %}
            <div class="item {% if loop.index0 == 0 %} active {% endif %}" style="height:300px;">

                {{ announcement.content }}
                {% if announcement.readMore %}
                    <a href="{{ _p.web }}news_list.php?id={{ announcement.id }}">{{ "More" | get_lang }}</a>
                {% endif %}
                <div class="carousel-caption">
                    <h5>{{ announcement.title }}</h5>
                </div>
            </div>
        {% endfor %}
    </div>

    <a class="left carousel-control" href="#announcements-slider" role="button" data-slide="prev">
        <i class="fa fa-chevron-left"></i>
    </a>
    <a class="right carousel-control" href="#announcements-slider" role="button" data-slide="next">
        <i class="fa fa-chevron-right"></i>
    </a>
</div>
