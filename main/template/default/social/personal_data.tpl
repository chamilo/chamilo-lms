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
        <div class="personal-data-intro">
            <div class="title">{{ 'PersonalDataIntroductionTitle' | get_lang }}</div>
            <div class="personal-data-intro-description">
                {{ personal_data.description }}
            </div>
        </div>
        <div class="personal-data-raw">
            <div class="title">{{ 'PersonalDataKeptOnYou' | get_lang }}</div>
            <div class="personal-data-raw-description">
                {{ personal_data.data }}
            </div>
        </div>
        <div class="personal-data-permissions">
            <div class="title">{{ 'PersonalDataPermissionsYouGaveUs' | get_lang }}</div>
            <div class="personal-data-raw-data">
            {{ personal_data.permissions }}
            </div>
        </div>
        <div class="personal-data-responsible">
            <div class="title">{{ 'PersonalDataResponsibleOrganization' | get_lang }}</div>
            <div class="personal-data-responsible-description">
            {{ personal_data.responsible }}
            </div>
        </div>
        <div class="personal-data-treatment">
            <div class="title">{{ 'PersonalDataTreatment' | get_lang }}</div>
            <div class="personal-data-treatment-description">
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataCollection' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.collection }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataRecording' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.recording }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataOrganization' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.organization }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataStructure' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.structure }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataConservation' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.conservation }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataAdaptation' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.adaptation }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataExtraction' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.extraction }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataConsultation' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.consultation }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataUsage' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.usage }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataCommunicationAndSharing' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.communication }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataInterconnection' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.interconnection }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataLimitation' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.limitation }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataDeletion' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.deletion }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataDestruction' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.destruction }}</div>
                </div>
                <div class="sub-section">
                    <div class="sub-title">{{ 'PersonalDataProfiling' | get_lang }}</div>
                    <div class="description">{{ personal_data.treatment.profiling }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}