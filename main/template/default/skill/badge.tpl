{% extends "default/layout/main.tpl" %}

{% block body %}
    <div class="span12">
        <h1 class="page-header">{{ 'Badges' | get_lang }}</h1>
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge_issuer.php">{{ 'IssuerDetails' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ 'Skills' | get_lang }}</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active">
                <div class="hero-unit">
                    <h1>Introducing Open Badges</h1>
                    <p class="lead">A new online standard to recognize and verify learning</p>
                    <p class="lead">
                        <a href="http://openbadges.org/">http://openbadges.org/</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
{% endblock %}
