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
            <div class="title">{{ 'PersonalDataPermissionsYouGaveUs' | get_lang }}</div>
            <div class="personal-data-raw-data">
                {{ personal_data.permissions.label }}
                {% if personal_data.permissions.accepted %}
                    <ul>
                        <li>{{ 'CurrentStatus' | get_lang }}: {{ personal_data.permissions.icon }} ({{ 'LegalAgreementAccepted' | get_lang }})</li>
                        <li>{{ 'Date' | get_lang }}: {{ personal_data.permissions.date }}</li>
                        <li>{{ 'LatestLoginInPlatform' | get_lang }}: {{ personal_data.permissions.last_login }}</li>
                    </ul>
                {% endif %}
                {{ personal_data.permissions.button }}
            </div>
        </div>
        <div class="panel personal-data-responsible">
            <div class="panel-title">{{ 'PersonalDataResponsibleOrganization' | get_lang }}</div>
            <div class="personal-data-responsible-description">
            {{ personal_data.responsible }}
            </div>
        </div>
        <div class="panel personal-data-treatment">
            <div class="panel-title">{{ 'PersonalDataTreatment' | get_lang }}</div>
            <div class="personal-data-treatment-description">
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataCollection' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.collection }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataRecording' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.recording }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataOrganization' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.organization }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataStructure' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.structure }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataConservation' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.conservation }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataAdaptationOrModification' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.adaptation }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataExtraction' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.extraction }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataConsultation' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.consultation }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataUsage' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.usage }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataCommunicationAndSharing' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.communication }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataInterconnection' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.interconnection }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataLimitation' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.limitation }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataDeletion' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.deletion }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataDestruction' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.destruction }}</div>
                </div>
                <div class="sub-section">
                    <div class="panel-sub-title">{{ 'PersonalDataProfiling' | get_lang }}</div>
                    <div class="panel-body">{{ personal_data.treatment.profiling }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}