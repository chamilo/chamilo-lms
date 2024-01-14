<template>
  <form @submit.prevent="onSubmit">
    <BaseInputText
      v-model="v$.title.$model"
      :error-text="v$.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.title.$error"
      :label="t('Assignment name')"
    />

    <BaseInputText v-model="assignment.description" :label="t('Description')" />

    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <BaseInputNumber
        id="qualification"
        v-model="assignment.qualification"
        :label="t('Maximum score')"
        :min="0"
        :step="0.01"
      />

      <BaseCheckbox
        id="make_calification_id"
        v-model="chkAddToGradebook"
        :label="t('Add to gradebook')"
        name="make_calification"
      />

      <div v-if="chkAddToGradebook">
        <BaseDropdrown
          v-model="v$.gradebookId.$model"
          :error-text="v$.gradebookId.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.gradebookId.$error"
          :label="t('Select assessment')"
          :options="gradebookCategories"
          input-id="gradebook-gradebook-id"
          name="gradebook_category_id"
          option-label="name"
        />

        <BaseInputNumber
          id="qualification"
          v-model="v$.weight.$model"
          :error-text="v$.weight.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.weight.$error"
          :label="t('Weight inside assessment')"
          :min="0"
          :step="0.01"
        />
      </div>

      <BaseCheckbox
        id="expiry_date"
        v-model="chkExpiresOn"
        :label="t('Enable handing over deadline (visible to learners)')"
        name="enableExpiryDate"
      />

      <BaseInputDate
        v-if="chkExpiresOn"
        id="expires_on"
        v-model="v$.expiresOn.$model"
        :error-text="v$.expiresOn.$errors.map((error) => error.$message).join('<br>')"
        :is-invalid="v$.expiresOn.$error"
        :label="t('Posted sending deadline')"
        show-time
      />

      <BaseCheckbox
        id="end_date"
        v-model="chkEndsOn"
        :label="t('Enable final acceptance date (invisible to learners)')"
        name="enableEndDate"
      />

      <BaseInputDate
        v-if="chkEndsOn"
        id="ends_on"
        v-model="v$.endsOn.$model"
        :error-text="v$.endsOn.$errors.map((error) => error.$message).join('<br>')"
        :is-invalid="v$.endsOn.$error"
        :label="t('Ends at (completely closed)')"
        show-time
      />

      <BaseCheckbox
        id="add-to-calendar"
        v-model="assignment.addToCalendar"
        :label="t('Add to calendar')"
        name="add_to_calendar"
      />

      <BaseDropdrown
        v-model="v$.allowTextAssignment.$model"
        :error-text="v$.allowTextAssignment.$errors.map((error) => error.$message).join('<br>')"
        :is-invalid="v$.allowTextAssignment.$error"
        :label="t('Document type')"
        :options="documentTypes"
        input-id="allow-text-assignment"
        name="allow_text_assignment"
        option-label="name"
      />
    </BaseAdvancedSettingsButton>

    <BaseButton :label="t('Save')" :disabled="isFormLoading" icon="save" is-submit type="secondary" />
  </form>
</template>

<script setup>
import BaseInputDate from "../basecomponents/BaseInputDate.vue";
import BaseInputText from "../basecomponents/BaseInputText.vue";
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue";
import BaseButton from "../basecomponents/BaseButton.vue";
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue";
import BaseDropdrown from "../basecomponents/BaseDropdown.vue";
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue";
import useVuelidate from "@vuelidate/core";
import { computed, reactive, ref, watch, watchEffect } from "vue"
import { maxValue, minValue, required } from "@vuelidate/validators";
import { useI18n } from "vue-i18n";
import { RESOURCE_LINK_PUBLISHED } from "../resource_links/visibility";
import { useCidReq } from "../../composables/cidReq";
import { useRoute } from "vue-router"

const props = defineProps({
  defaultAssignment: {
    type: Object,
    required: false,
    default: () => null,
  },
  isFormLoading: {
    type: Boolean,
    required: false,
    default: () => false,
  }
});

const emit = defineEmits(["submit"]);

const route = useRoute();
const { t } = useI18n();
const { cid, sid, gid } = useCidReq();

const showAdvancedSettings = ref(false);

const chkAddToGradebook = ref(false);
const chkExpiresOn = ref(false);
const chkEndsOn = ref(false);

const gradebookCategories = ref([{ name: "Default", id: 1 }]);
const documentTypes = ref([
  { name: t("Allow files or online text"), value: 0 },
  { name: t("Allow only text"), value: 1 },
  { name: t("Allow only files"), value: 2 },
]);

const assignment = reactive({
  title: "",
  description: "",
  qualification: 0,
  gradebookId: gradebookCategories.value[0],
  weight: 0,
  expiresOn: new Date(),
  endsOn: new Date(),
  addToCalendar: false,
  allowTextAssignment: documentTypes.value[2],
});

watchEffect(() => {
  const defaultAssignment = props.defaultAssignment

  if (!defaultAssignment) {
    return
  }

  assignment.title = defaultAssignment.title
  assignment.description = defaultAssignment.description

  assignment.qualification = defaultAssignment.qualification
  assignment.addToCalendar = defaultAssignment.assignment.eventCalendarId > 0

  if (defaultAssignment.weight > 0) {
    chkAddToGradebook.value = true
    //assignment.gradebookId.id = defaultAssignment.gradebookCategoryId
    assignment.weight = defaultAssignment.weight
  }

  if (defaultAssignment.assignment.expiresOn) {
    chkExpiresOn.value = true
    assignment.expiresOn = new Date(defaultAssignment.assignment.expiresOn)
  }

  if (defaultAssignment.assignment.endsOn) {
    chkEndsOn.value = true
    assignment.endsOn = new Date(defaultAssignment.assignment.endsOn)
  }

  assignment.allowTextAssignment = documentTypes.value.find(
    (documentType) => documentType.value === defaultAssignment.allowTextAssignment,
  )

  if (
    defaultAssignment.qualification ||
    defaultAssignment.assignment.eventCalendarId ||
    defaultAssignment.weight ||
    defaultAssignment.assignment.expiresOn ||
    defaultAssignment.assignment.endsOn ||
    defaultAssignment.allowTextAssignment
  ) {
    showAdvancedSettings.value = true
  }
})

const rules = computed(() => {
  const localRules = {
    title: { required, $autoDirty: true },
  };

  if (showAdvancedSettings.value) {
    if (chkAddToGradebook.value) {
      localRules.gradebookId = { required };

      localRules.weight = { required };
    }

    if (chkExpiresOn.value) {
      localRules.expiresOn = { required, $autoDirty: true };

      if (chkEndsOn.value) {
        localRules.expiresOn.maxValue = maxValue(assignment.endsOn);
      }
    }

    if (chkEndsOn.value) {
      localRules.endsOn = { required, $autoDirty: true };

      if (chkExpiresOn.value) {
        localRules.endsOn.minValue = minValue(assignment.expiresOn);
      }
    }

    localRules.allowTextAssignment = { required };
  }

  return localRules;
});

const v$ = useVuelidate(rules, assignment);

const onSubmit = async () => {
  const result = await v$.value.$validate();

  if (!result) {
    return;
  }

  const publicationStudent = {
    title: assignment.title,
    description: assignment.description,
    assignment: {
      expiresOn: null,
      endsOn: null,
    },
    parentResourceNode: route.params.node * 1,
    resourceLinkList: [
      {
        cid,
        sid,
        gid,
        visibility: RESOURCE_LINK_PUBLISHED,
      },
    ],
  };

  if (showAdvancedSettings.value) {
    publicationStudent.qualification = assignment.qualification;

    publicationStudent.addToCalendar = assignment.addToCalendar;

    if (chkAddToGradebook.value) {
      publicationStudent.gradebookCategoryId = assignment.gradebookId.id;
      publicationStudent.weight = assignment.weight;
    }

    if (chkExpiresOn.value) {
      publicationStudent.assignment.expiresOn = assignment.expiresOn;
    }

    if (chkEndsOn.value) {
      publicationStudent.assignment.endsOn = assignment.endsOn;
    }

    publicationStudent.allowTextAssignment = assignment.allowTextAssignment.value;
  }

  if (props.defaultAssignment) {
    publicationStudent["@id"] = props.defaultAssignment["@id"]
    publicationStudent.assignment["@id"] = props.defaultAssignment.assignment["@id"]
  }

  emit("submit", publicationStudent);
};
</script>