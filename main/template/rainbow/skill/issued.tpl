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
    </div>
    <div class="col-md-7">
        <p class="lead" style="color: #162A83;">{{ issue_info.user_complete_name }}</p>
        <ul class="fa-ul">
            <li><p style="color: #162A83;"><em class="fa-li fa fa-clock-o fa-fw"></em>{{ 'SkillAcquiredAt'|get_lang }} {{ issue_info.datetime }}</p>
                {% if issue_info.argumentation %}
                            <p style="color: #162A83;">
                                {{ 'UserXIndicated'|get_lang|format(issue_info.argumentation_author_name) }}
                            </p>
	    <ul><p style="font-style: italic; color: #162A83;">{{ issue_info.argumentation }}</p></ul>
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
            <p style="color: #162A83;">
                <em class="fa fa-comment-o fa-fw" aria-hidden="true"></em>
                {{ 'XComments'|get_lang|format(issue_info.comments|length) }}
                <ul class="fa-ul">
                   {% if issue_info.comments %}
                        {% for comment in issue_info.comments %}
                           <li><ul>
                            <article class="media">
                                <div class="media-body">
                                    <p style="font-style: italic; color: #162A83;">{{ comment.text }}</p>
                                </div>
                            </article>
                            </ul></li>
                        {% endfor %}
                    {% endif %}
                </ul>
            </p>
        {% endif %}
    </div>
</div>
{% if allow_download_export %}
    <script>
        $(document).on('ready', function () {
            $('#badge-export-button').on('click', function (e) {
                e.preventDefault();
                OpenBadges.issue({{ issue_info.badge_assertion|json_encode() }});
            });
        });
    </script>
{% endif %}
