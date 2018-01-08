<div class="col-md-12">
    <ul class="nav nav-tabs">
        <li class="active">
            <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
        </li>
        <li>
            <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ "CurrentBadges" | get_lang }}</a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active">
            <div class="openbadges-introduction">
                <h1 class="title">
                    <img src="{{ 'badges.png' | icon(64) }}" width="64" height="64">{{ 'OpenBadgesTitle' | get_lang }}
                </h1>
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <p class="lead">
                            {{ 'OpenBadgesBannerText' | get_lang }}
                        </p>
                        <p class="lead">
                            {{ 'OpenBadgesIntroduction' | get_lang }}
                        </p>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <img class="img-responsive" src="{{ 'openbadges.png' | icon() }}">
                    </div>
                </div>

                <h3 class="sub-title">{{ 'OpenBadgesActionCall' | get_lang }}</h3>
                <div class="block-content">
                    <div class="block-title">{{ 'IssuerDetails' | get_lang }}</div>
                    <p>{{ 'Name' | get_lang }} : {{ _s.institution }}</p>
                    <p>URL: {{ _p.web }}</p>
                    <div class="block-title">{{ 'BackpackDetails' | get_lang }}</div>
                    <p>URL: {{ backpack }}</p>
                    <p>{{ 'TheBadgesWillBeSentToThatBackpack' | get_lang }}</p>

                </div>
            </div>
        </div>
    </div>
</div>
