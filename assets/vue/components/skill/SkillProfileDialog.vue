<script setup>
import { computed, reactive, unref } from "vue"
import { useI18n } from "vue-i18n"

import BaseTextArea from "../basecomponents/BaseTextArea.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"

import { createProfile, updateProfile } from "../../services/skillProfileService"

const { t } = useI18n()

const profile = reactive({
  title: "",
  description: "",
})

const isVisible = defineModel("visible", {
  required: true,
  type: Boolean,
})

const skills = defineModel("skills", {
  type: Array,
  required: false,
})

const emit = defineEmits(["saved"])

async function saveProfile() {
  if (profile["@id"]) {
    await updateProfile({
      iri: profile["@id"],
      title: profile.title,
      description: profile.description,
    })
  } else {
    await createProfile({
      title: profile.title,
      description: profile.description,
      skills: skills.value.map((skill) => ({ skill: skill["@id"] })),
    })
  }

  isVisible.value = false

  emit("saved", unref(profile))
}
</script>

<template>
  <BaseDialog
    :title="t('Skill profile')"
    v-model:is-visible="isVisible"
  >
    <BaseInputText
      id="name_profile"
      v-model="profile.title"
      :label="t('Title')"
    />
    <BaseTextArea
      v-model="profile.description"
      :label="t('Description')"
    />

    <template #footer>
      <BaseButton
        :label="t('Cancel')"
        icon="close"
        type="black"
        @click="isVisible = false"
      />
      <BaseButton
        :label="t('Save')"
        icon="save"
        type="primary"
        @click="saveProfile"
      />

      <pre>{{ skillIdList }}</pre>
    </template>
  </BaseDialog>
</template>
