{% extends "default/layout/main.tpl" %}

{% block body %}
    <div class="span12">
        <h1 class="page-header">{{ 'Badges' | get_lang }}</h1>
        <ul class="nav nav-tabs">
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li class="active">
                <a href="{{ _p.web_main }}admin/skill_badge_issuer.php">{{ 'IssuerInfo' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ 'Skills' | get_lang }}</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active">
                <form action="{{ _p.web_self }}" class="form-horizontal">
                    <fieldset>
                        <legend>{{ 'IssuerInfo' | get_lang }}</legend>
                        <div class="control-group">
                            <label class="control-label">{{ 'Name' | get_lang }}</label>
                            <div class="controls">
                                <span class="uneditable-input input-xxlarge">{{ _s.institution }}</span>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">{{ 'URL' | get_lang }}</label>
                            <div class="controls">
                                <span class="uneditable-input input-xxlarge">{{ _p.web }}</span>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>{{ 'BackpackDetails' | get_lang }}</legend>
                        <div class="control-group">
                            <label class="control-label">{{ 'URL' | get_lang }}</label>
                            <div class="controls">
                                <span class="uneditable-input input-xxlarge">{{ backpack }}</span>
                                <p class="help-block">{{ 'TheBadgesWillBeSentToThatBackpack' | get_lang }}</p>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
{% endblock %}
