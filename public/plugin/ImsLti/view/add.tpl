<div class="ims-lti-create-page">
    {{ form|raw }}
</div>

<style>
    .ims-lti-create-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px 24px;
    }

    .ims-lti-create-page form,
    .ims-lti-create-page #ism_lti_create_tool {
        width: 100% !important;
        max-width: 1200px !important;
    }

    .ims-lti-create-page .form-group,
    .ims-lti-create-page .field,
    .ims-lti-create-page .p-field {
        width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 18px !important;
    }

    .ims-lti-create-page .control-label,
    .ims-lti-create-page label,
    .ims-lti-create-page .col-sm-2,
    .ims-lti-create-page .col-md-2 {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        text-align: left !important;
        margin-bottom: 6px !important;
        float: none !important;
    }

    .ims-lti-create-page .col-sm-10,
    .ims-lti-create-page .col-sm-9,
    .ims-lti-create-page .col-sm-8,
    .ims-lti-create-page .col-sm-6,
    .ims-lti-create-page .col-md-10,
    .ims-lti-create-page .col-md-9,
    .ims-lti-create-page .col-md-8,
    .ims-lti-create-page .col-md-6 {
        width: 100% !important;
        max-width: 100% !important;
        float: none !important;
        margin-left: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .ims-lti-create-page input[type="text"],
    .ims-lti-create-page input[type="url"],
    .ims-lti-create-page input[type="password"],
    .ims-lti-create-page input[type="email"],
    .ims-lti-create-page textarea,
    .ims-lti-create-page select,
    .ims-lti-create-page .form-control,
    .ims-lti-create-page .p-inputtext,
    .ims-lti-create-page .p-dropdown {
        width: min(100%, 720px) !important;
        max-width: 720px !important;
        min-width: 320px !important;
        box-sizing: border-box !important;
    }

    .ims-lti-create-page textarea {
        min-height: 120px !important;
    }

    .ims-lti-create-page .p-float-label,
    .ims-lti-create-page .field:has(.p-float-label) {
        display: block !important;
        width: min(100%, 720px) !important;
        max-width: 720px !important;
        position: relative !important;
        padding-top: 12px !important;
    }

    .ims-lti-create-page .p-float-label > label {
        background: #fff !important;
        padding: 0 6px !important;
        z-index: 2 !important;
    }

    .ims-lti-create-page .p-float-label > input,
    .ims-lti-create-page .p-float-label > textarea,
    .ims-lti-create-page .p-float-label > .p-inputtext {
        position: relative !important;
        z-index: 1 !important;
    }

    .ims-lti-create-page .radio,
    .ims-lti-create-page .checkbox {
        display: block !important;
        margin-bottom: 8px !important;
    }

    .ims-lti-create-page .btn,
    .ims-lti-create-page button {
        margin-top: 10px !important;
    }
</style>
