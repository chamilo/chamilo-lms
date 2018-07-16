{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        <div class="social-network-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
        </div>
    </div>
    <div class="col-md-9">
        <div class="panel">
            <div class="panel-title">{{ 'PersonalDataIntroductionTitle' | get_lang }}</div>
            <div class="personal-data-intro-description">
                {{ 'PersonalDataIntroductionText' | get_lang }}
            </div>
        </div>
        <div class="panel personal-data-raw">
            <div class="panel-title">{{ 'PersonalDataKeptOnYou' | get_lang }}</div>
            <div class="export-button pull-right">
                <a href="personal_data.php?export=1">
                    {{ personal_data.data_export_icon }}
                </a>
            </div>
            <div class="personal-data-raw-description">
                {{ personal_data.data }}
            </div>
        </div>
        <div class="panel personal-data-permissions">
            <div class="panel-title">{{ 'PersonalDataPermissionsYouGaveUs' | get_lang }}</div>
            <div class="personal-data-raw-data">
                {{ personal_data.permissions.label }}
                <ul>
                {% if personal_data.permissions.accepted %}
                        <li>{{ 'CurrentStatus' | get_lang }}: {{ personal_data.permissions.icon }} ({{ 'LegalAgreementAccepted' | get_lang }})</li>
                        <li>{{ 'Date' | get_lang }}: {{ personal_data.permissions.date }}</li>
                {% endif %}
                <li>{{ personal_data.permissions.button }}</li>
                </ul>
            </div>
        </div>
        <div class="panel personal-data-responsible">
            <div class="panel-title">{{ 'PersonalDataResponsibleOrganizationTitle' | get_lang }}</div>
            <div class="personal-data-responsible-description">
            {{ personal_data.responsible }}
            </div>
        </div>
        <div class="panel personal-data-treatment">
            <div class="panel-title">{{ 'PersonalDataTreatment' | get_lang }}</div>
            <div class="personal-data-treatment-description">
                {% for treatment in personal_data.treatment %}
                    {% if treatment.content %}
                    <div class="sub-section">
                        <div class="panel-sub-title">{{ treatment.title }}</div>
                        <div class="panel-body">{{ treatment.content }}</div>
                    </div>
                    {% endif %}
                {% endfor %}
            </div>
        </div>
    </div>
</div>
{% endblock %}