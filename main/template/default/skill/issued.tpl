<div class="row issued">
    <div class="col-md-5">
        <div class="thumbnail">
            <figure class="text-center">
                <img class="img-responsive center-block" src="{{ issue_info.skill_badge_image }}" alt="{{ issue_info.skill_name }}">
                <figcaption>
                    <p class="lead">{{ issue_info.skill_name }}</p>
                    {% if issue_info.skill_short_code %}
                        <p>{{ issue_info.skill_short_code }}</p>
                    {% endif %}
                </figcaption>
            </figure>
            <div class="caption">
                {% if issue_info.skill_description %}
                    <p>{{ issue_info.skill_description }}</p>
                {% endif %}
                {% if issue_info.skill_criteria %}
                    <h4>{{ 'CriteriaToEarnTheBadge'|get_lang }}</h4>
                    <p>{{ issue_info.skill_criteria }}</p>
                {% endif %}
            </div>
        </div>

        {% if badge_error %}
            <hr>
            <div class="alert alert-danger"> {{ 'BakedBadgeProblem'|get_lang }}</div>
        {% elseif not personal_badge is empty %}
            <p class="text-center">
                <a href="{{ personal_badge }}" class="btn btn-primary" target="_new" download="badge">
                    <em class="fa fa-download fa-fw"></em> {{ 'DownloadBadge'|get_lang }}
                </a>
            </p>
        {% endif %}

        {% if allow_export %}
            <hr>
            <p class="text-center">
                <a href="#" class="btn btn-success" id="badge-export-button">
                    <em class="fa fa-external-link-square fa-fw"></em> {{ 'ExportBadge'|get_lang }}
                </a>
            </p>
            <div class='col-md-12 text-center'>
                <h5><b> {{ 'ShareWithYourFriends' | get_lang }} </b></h5>
                <a href="http://www.facebook.com/sharer.php?u={{ _p.web }}badge/{{ issue_info.id }}" target="_new">
                    <em class='fa fa-facebook-square fa-3x text-info' aria-hidden='true'></em>
                </a>
                <a href="https://twitter.com/home?status={{ 'IHaveObtainedSkillXOnY' | get_lang |format(issue_info.skill_name, _s.site_name)}} - {{ _p.web }}badge/{{ issue_info.id }}" target="_new">
                    <em class='fa fa-twitter-square fa-3x text-light' aria-hidden='true'></em>
                </a>
                <a href="{{ 'https://www.linkedin.com/profile/add?' ~ {
                'certId':issue_info.id,
                'certUrl':_p.web ~ "badge/" ~ issue_info.id,
                'isFromA2p':'true',
                'issueMonth':issue_info.month,
                'issueYear': issue_info.year,
                'name':'BadgeXTitle'|get_lang|format(issue_info.skill_name),
                'organizationId':issue_info.linkedin_organization_id
                }|url_encode }}" target="_new">
                    <em class='fa fa-linkedin-square fa-3x txt-linkedin' aria-hidden='true'></em>
                </a>
            </div>
        {% endif %}
    </div>
    <div class="col-md-7">
        <h5>{{ 'RecipientDetails'|get_lang }}</h5>
        <p class="lead">{{ issue_info.user_complete_name }}</p>
        <h4>{{ 'SkillAcquiredAt'|get_lang }}</h4>
        <ul class="fa-ul">
            <li>
                {% if issue_info.source_name %}
                    <em class="fa-li fa fa-clock-o fa-fw"></em>
                    {{ 'TimeXThroughCourseY'|get_lang|format(issue_info.datetime, issue_info.source_name) }}
                {% else %}
                    <em class="fa-li fa fa-clock-o fa-fw"></em>
                    {{ issue_info.datetime }}
                {% endif %}
                {% if issue_info.argumentation %}
                    {% if issue_info.argumentation %}
                        <b>
                            <p style="font-style: italic;">
                                {{ 'UserXIndicated'|get_lang|format(issue_info.argumentation_author_name) }}
                            </p>
                        </b>
                    {% endif %}
                    <p>{{ issue_info.argumentation }}</p>
                {% endif %}
            </li>
        </ul>

        {% if show_level %}
        <h4>{{ 'AcquiredLevel'|get_lang }}</h4>
        <ul class="fa-ul">
            <li>
                <em class="fa-li fa fa-check-circle-o fa-fw"></em> {{ issue_info.acquired_level }}
            </li>
        </ul>
        {% endif %}

        {% if allow_comment %}
            {% if show_level %}
                <hr>
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <em class="fa fa-check-circle-o fa-fw" aria-hidden="true"></em>
                        {{ 'ChangeAcquiredLevel'|get_lang }}
                    </div>
                    <div class="panel-body">
                        {{ acquired_level_form }}
                    </div>
                </div>
            {% endif %}
            <hr>
            <div class="panel panel-info">
                <div class="panel-heading">
                    <em class="fa fa-comment-o fa-fw" aria-hidden="true"></em>
                    {{ 'XComments'|get_lang|format(issue_info.comments|length) }}
                    /
                    <em class="fa fa-thumbs-o-up fa-fw" aria-hidden="true"></em>
                    {{ 'AverageRatingX'|get_lang|format(issue_info.feedback_average) }}
                </div>
                <div class="panel-body">
                    {{ comment_form }}
                    {% if issue_info.comments %}
                        <hr>
                        {% for comment in issue_info.comments %}
                            <article class="media">
                                <div class="media-body">
                                    <h4 class="media-heading">{{ comment.giver_complete_name }}</h4>
                                    <p><small>{{ comment.datetime }}</small></p>
                                    <p>{{ comment.text }}</p>
                                </div>
                                <div class="media-right text-right">
                                    <div style="width: 80px;">
                                        {% if comment.value %}
                                            <em class="fa fa-certificate fa-fw" aria-label="{{ 'AverageRating' }}"></em>
                                            <span class="sr-only">{{ 'AverageRating' }}</span> {{ comment.value }}
                                        {% endif %}
                                    </div>
                                </div>
                            </article>
                        {% endfor %}
                    {% endif %}
                </div>
            </div>
        {% else %}
            <hr>
            <p class="lead">
                <em class="fa fa-comment-o fa-fw" aria-hidden="true"></em>
                {{ 'XComments'|get_lang|format(issue_info.comments|length) }}
                /
                <em class="fa fa-thumbs-o-up fa-fw" aria-hidden="true"></em>
                {{ 'AverageRatingX'|get_lang|format(issue_info.feedback_average) }}
            </p>
        {% endif %}
    </div>
</div>
{% if allow_export %}
    <script>
        $(document).on('ready', function () {
            $('#badge-export-button').on('click', function (e) {
                e.preventDefault();
                OpenBadges.issue({{ issue_info.badge_assertion|json_encode() }});
            });
        });
    </script>
{% endif %}