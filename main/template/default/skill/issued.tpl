<div class="row">
    <div class="col-md-4">
        <figure class="thumbnail">
            <img class="img-responsive" src="{{ skill_info.badge_image }}" alt="{{ skill_info.name }}">
            <figcaption class="caption text-center">
                <p class="lead">{{ skill_info.name }}</p>
            </figcaption>
        </figure>
        <h3>{{ 'SkillAcquiredAt'|get_lang }}</h3>
        <ul class="fa-ul">
            {% for course in skill_info.courses %}
                <li>
                    <p>
                        <em class="fa fa-clock-o fa-fw"></em> {{ 'TimeXThroughCourseY'|get_lang|format(course.date_issued, course.name) }}
                    </p>
                </li>
            {% endfor %}
        </ul>
        {% if allow_export %}
            <p class="text-center">
                <a href="#" class="btn btn-success" id="badge-export-button">
                    <i class="fa fa-external-link-square fa-fw"></i> {{ 'ExportBadge'|get_lang }}
                </a>
            </p>
        {% endif %}
    </div>
    <div class="col-md-8">
        <h3>{{ 'RecipientDetails'|get_lang }}</h3>
        <p class="lead">{{ user_info.complete_name }}</p>
        <h3>{{ 'BadgeDetails'|get_lang }}</h3>
        <h3>{{ 'Name'|get_lang }}</h3>
        <p>{{ skill_info.name }}</p>
        {% if skill_info.short_code %}
            <h3>{{ 'ShortCode'|get_lang }}</h3>
            <p>{{ skill_info.short_code }}</p>
        {% endif %}
        <h3>{{ 'Description'|get_lang }}</h3>
        <p>{{ skill_info.description }}</p>
        <h3>{{ 'CriteriaToEarnTheBadge'|get_lang }}</h3>
        <p>{{ skill_info.criteria }}</p>
    </div>
</div>
{% if allow_export %}
    <script>
        $(document).on('ready', function () {
            $('#badge-export-button').on('click', function (e) {
                e.preventDefault();

                OpenBadges.issue({{ assertions|json_encode() }});
            });
        });
    </script>
{% endif %}
