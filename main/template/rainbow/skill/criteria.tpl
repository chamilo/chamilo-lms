<h2 class="page-header">{{ 'BadgeDetails'|get_lang }}</h2>
<article>
    <div class="row">
        <div class="col-md-3">
            <figure class="thumbnail">
                <img class="img-responsive" src="{{ skill_info.badge_image }}" alt="{{ skill_info.name }}">
                <figcaption class="caption">
                    <p class="name-badge text-center">{{ skill_info.name }}</p>
                    {% if skill_info.short_code %}
                        <p class="code-badge text-center"><i class="fa fa-shield"></i> {{ skill_info.short_code }}</p>
                    {% endif %}
                </figcaption>
            </figure>
        </div>
        <div class="col-md-9">
            <h4 class="title-badge">{{ 'Name'|get_lang }}</h4>
            <p class="lead">{{ skill_info.name }}</p>
            <h4 class="title-badge">{{ 'Description'|get_lang }}</h4>
            <p>{{ skill_info.description }}</p>
            <h4 class="title-badge">{{ 'CriteriaToEarnTheBadge'|get_lang }}</h4>
            <p>{{ skill_info.criteria }}</p>
        </div>
    </div>
</article>
