<div class="row issued">
    <div class="col-md-4">
        <figure class="thumbnail">
            <img class="img-responsive" src="{{ skill_info.badge_image }}" alt="{{ skill_info.name }}">
            <figcaption class="caption text-center">
                <p class="name-badge text-center">{{ skill_info.name }}</p>
            </figcaption>
        </figure>
            <div class="panel panel-default">
                <div class="panel-heading">{{ 'SkillAcquiredAt'|get_lang }}</div>
                <div class="panel-body">
                    {% for course in skill_info.courses %}
                    <p>
                        {% if course.name %}
                            <em class="fa fa-clock-o fa-fw"></em> {{ 'TimeXThroughCourseY'|get_lang|format(course.date_issued, course.name) }}
                        {% else %}
                            <em class="fa fa-clock-o fa-fw"></em> {{ course.date_issued }}
                        {% endif %}
                    </p>
                    {% endfor %}
                </div>
            </div>    
        {% if allow_export %}
            <p class="text-center">
                <a href="#" class="btn btn-success" id="badge-export-button">
                    <em class="fa fa-external-link-square fa-fw"></em> {{ 'ExportBadge'|get_lang }}
                </a>
            </p>
        {% endif %}
    </div>
    <div class="col-md-8">
        <div class="panel panel-default">
        <div class="panel-body">
        <h4 class="title-badge">{{ 'RecipientDetails'|get_lang }}</h4>
        <p class="lead">{{ user_info.complete_name }}</p>
        <h4 class="title-badge">{{ 'BadgeDetails'|get_lang }}</h4>
        <h4 class="title-badge">{{ 'Name'|get_lang }}</h4>
        <p>{{ skill_info.name }}</p>
        {% if skill_info.short_code %}
            <h4 class="title-badge">{{ 'ShortCode'|get_lang }}</h4>
            <p>{{ skill_info.short_code }}</p>
        {% endif %}
        <h4 class="title-badge">{{ 'Description'|get_lang }}</h4>
        <p>{{ skill_info.description }}</p>
        <h4 class="title-badge">{{ 'CriteriaToEarnTheBadge'|get_lang }}</h4>
        <p>{{ skill_info.criteria }}</p>
        </div>
        </div>
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
