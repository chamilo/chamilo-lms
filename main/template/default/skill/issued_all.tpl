{% for badge in user_badges %}
    <div class="row issued">
        <div class="col-md-5">
            <div class="thumbnail">
                <figure class="text-center">
                    <img class="img-responsive center-block" src="{{ badge.issue_info.skill_badge_image }}" alt="{{ badge.issue_info.skill_name }}">
                    <figcaption>
                        <p class="lead">{{ badge.issue_info.skill_name }}</p>
                        {% if badge.issue_info.skill_short_code %}
                            <p>{{ badge.issue_info.skill_short_code }}</p>
                        {% endif %}
                    </figcaption>
                </figure>
                <div class="caption">
                    {% if badge.issue_info.skill_description %}
                        <p>{{ badge.issue_info.skill_description }}</p>
                    {% endif %}
                    {% if badge.issue_info.skill_criteria %}
                        <h3>{{ 'CriteriaToEarnTheBadge'|get_lang }}</h3>
                        <p>{{ badge.issue_info.skill_criteria }}</p>
                    {% endif %}
                </div>
            </div>
            {% if badge.allow_download_export %}
                {% if badge.badge_error %}
                    <hr>
                    <div class="alert alert-danger"> {{ 'BakedBadgeProblem'|get_lang }}</div>
                {% else %}
                    <p class="text-center">
                        <a href="{{ badge.personal_badge }}" class="btn btn-primary" target="_new" download="badge">
                            <em class="fa fa-download fa-fw"></em> {{ 'DownloadBadge'|get_lang }}
                        </a>
                    </p>
                {% endif %}
                <hr>
                <p class="text-center">
                    <a href="#" class="btn btn-success" id="badge-export-button-{{ badge.issue_info.id }}">
                        <em class="fa fa-external-link-square fa-fw"></em> {{ 'ExportBadge'|get_lang }}
                    </a>
                </p>
                {% if not 'hide_social_media_links'|api_get_configuration_value %}
                <div class='col-md-12 text-center'>
                    <h5><b> {{ 'ShareWithYourFriends' | get_lang }} </b></h5>
                    <a href="http://www.facebook.com/sharer.php?u={{ _p.web }}badge/{{ badge.issue_info.id }}" target="_new">
                        <em class='fa fa-facebook-square fa-3x text-info' aria-hidden='true'></em>
                    </a>
                    <a href="https://twitter.com/home?status={{ 'IHaveObtainedSkillXOnY' | get_lang |format(badge.issue_info.skill_name, _s.site_name)}} - {{ _p.web }}badge/{{ badge.issue_info.id }}" target="_new">
                        <em class='fa fa-twitter-square fa-3x text-light' aria-hidden='true'></em>
                    </a>


                    <a href="{{ 'https://www.linkedin.com/profile/add?' ~ {
                    'certId':badge.issue_info.id,
                    'certUrl':_p.web ~ "badge/" ~ badge.issue_info.id,
                    'isFromA2p':'true',
                    'issueMonth':badge.issue_info.month,
                    'issueYear': badge.issue_info.year,
                    'name':'BadgeXTitle'|get_lang|format(badge.issue_info.skill_name),
                    'organizationId':badge.issue_info.linkedin_organization_id
                    }|url_encode }}" target="_new">
                        <em class='fa fa-linkedin-square fa-3x txt-linkedin' aria-hidden='true'></em>
                    </a>
                </div>
                {% endif %}
            {% endif %}
        </div>
        <div class="col-md-7">
            <h5>{{ 'RecipientDetails'|get_lang }}</h5>
            <p class="lead">{{ badge.issue_info.user_complete_name }}</p>
            <h4 class="bage-username">{{ 'SkillAcquiredAt'|get_lang }}</h4>
            <ul class="fa-ul">
                <li class="badge-item">
                    {% if badge.issue_info.source_name %}
                        <em class="fa fa-clock-o fa-fw"></em> {{ 'TimeXThroughCourseY'|get_lang|format(badge.issue_info.datetime, badge.issue_info.source_name) }}
                    {% else %}
                        <em class="fa fa-clock-o fa-fw"></em> {{ badge.issue_info.datetime }}
                    {% endif %}
                    {% if badge.issue_info.argumentation %}
                        {% if badge.issue_info.argumentation %}
                            <br>
                            <p>{{ 'UserXIndicated'|get_lang|format(badge.issue_info.argumentation_author_name) }} </p>
                        {% endif %}
                        <p class="msg">{{ badge.issue_info.argumentation }}</p>
                    {% endif %}
                </li>
            </ul>

            {% if show_level %}
            <h4>{{ 'AcquiredLevel'|get_lang }}</h4>
            <ul class="fa-ul">
                <li>
                    <em class="fa-li fa fa-check-circle-o fa-fw"></em> {{ badge.issue_info.acquired_level }}
                </li>
            </ul>
            {% endif %}

            {% if badge.allow_comment %}
                <hr>
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <em class="fa fa-check-circle-o fa-fw" aria-hidden="true"></em> {{ 'ChangeAcquiredLevel'|get_lang }}
                    </div>
                    <div class="panel-body">
                        {{ badge.acquired_level_form }}
                    </div>
                </div>
                <hr>
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <em class="fa fa-comment-o fa-fw" aria-hidden="true"></em> {{ 'XComments'|get_lang|format(badge.issue_info.comments|length) }}
                        /
                        <em class="fa fa-thumbs-o-up fa-fw" aria-hidden="true"></em> {{ 'AverageRatingX'|get_lang|format(badge.issue_info.feedback_average) }}
                    </div>
                    <div class="panel-body">
                        {{ badge.comment_form }}
                        <hr>
                        {% for comment in badge.issue_info.comments %}
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
                        {% else %}
                            <p>{{ 'WithoutComment'|get_lang }}</p>
                        {% endfor %}
                    </div>
                </div>
            {% else %}
                <hr>
                <p class="lead">
                    <em class="fa fa-comment-o fa-fw" aria-hidden="true"></em> {{ 'XComments'|get_lang|format(badge.issue_info.comments|length) }}
                    /
                    <em class="fa fa-thumbs-o-up fa-fw" aria-hidden="true"></em> {{ 'AverageRatingX'|get_lang|format(badge.issue_info.feedback_average) }}
                </p>
            {% endif %}
        </div>
    </div>
    {% if badge.allow_download_export %}
        <script>
            $(document).on('ready', function () {
                $('#badge-export-button-{{ badge.issue_info.id }}').on('click', function (e) {
                    e.preventDefault();

                    OpenBadges.issue({{ badge.issue_info.badge_assertion|json_encode() }});
                });
            });
        </script>
    {% endif %}
    <br />
    <br />
{% endfor %}