<h1 class="page-header">{{ 'BadgeDetails'|get_lang }}</h1>
<article>
    <div class="row">
        <div class="col-md-3">
            <figure class="thumbnail">
                <img class="img-responsive" src="{{ skill_info.badge_image }}" alt="{{ skill_info.name }}">
                <figcaption class="caption">
                    <h2 class="text-center">{{ skill_info.name }}</h2>
                    {% if skill_info.short_code %}
                        <p class="lead text-center">{{ skill_info.short_code }}</p>
                    {% endif %}
                </figcaption>
            </figure>
        </div>
        <div class="col-md-9">
            <h3>{{ 'Name'|get_lang }}</h3>
            <p class="lead">{{ skill_info.name }}</p>
            <h3>{{ 'Description'|get_lang }}</h3>
            <p>{{ skill_info.description }}</p>
            <h3>{{ 'CriteriaToEarnTheBadge'|get_lang }}</h3>
            <p>{{ skill_info.criteria }}</p>
        </div>
    </div>
</article>
