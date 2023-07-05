<template>
  <form @submit.prevent="onSubmit">
    <div class="field">
      <h3 v-t="'Create assignment'" />
    </div>

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
import BaseInputText from "../../components/basecomponents/BaseInputText.vue";
import { useI18n } from "vue-i18n";
import { computed, onMounted, reactive, ref } from "vue";
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue";
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue";
import BaseDropdrown from "../../components/basecomponents/BaseDropdown.vue";
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue";
import BaseInputDate from "../../components/basecomponents/BaseInputDate.vue";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import useVuelidate from "@vuelidate/core";
import { maxValue, minValue, required } from "@vuelidate/validators";
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility";
import { useCidReq } from "../../composables/cidReq";
import { useNotification } from "../../composables/notification";
import { useRouter } from "vue-router";

const { t } = useI18n();
const { cid, sid, gid } = useCidReq();
const router = useRouter();

const { showSuccessNotification, showErrorNotification } = useNotification();

const course = ref(null);

onMounted(async () => {
  try {
    const { data } = await axios.get(`${ENTRYPOINT}courses/${cid}`);

    course.value = data;
  } catch (e) {
    console.log(e);
  }
});

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

const isFormLoading = ref(false);

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
    parentResourceNode: course.value.resourceNode.replace("/api/resource_nodes/", "") * 1,
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

    if (chkAddToGradebook.value) {
      publicationStudent.addToCalendar = true;
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

  isFormLoading.value = true;

  axios
    .post(`${ENTRYPOINT}c_student_publications`, publicationStudent)
    .then(({ data }) => {
      console.log("cstudentpublication", data);

      showSuccessNotification(t("Assignment created"));

      router.push({ name: "AssigmnentsList", query: { cid, sid, gid } });
    })
    .catch((error) => showErrorNotification(error))
    .finally(() => (isFormLoading.value = false));
};
</script>
