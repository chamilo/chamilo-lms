<h4 class="page-header">{{ "SystemAnnouncements" | get_lang }}</h4>
   
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
          {{ announcement.content }}
            {% if announcement.readMore %}
                <a href="{{ _p.web }}news_list.php?id={{ announcement.id }}">{{ "More" | get_lang }}</a>
            {% endif %}
          <div class="carousel-caption">
              <h3>{{ announcement.title }}</h3>
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