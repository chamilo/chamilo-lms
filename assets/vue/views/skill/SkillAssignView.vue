<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"

import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import * as skillService from "../../services/skillService"

const { t } = useI18n()
const route = useRoute()
const { showErrorNotification, showSuccessNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const userId = computed(() => Number(route.params.userId || 0))

const loading = ref(false)
const saving = ref(false)
const context = ref(null)
const initialSkillId = Number(route.query.skillId || 0)
const skillId = ref(initialSkillId > 0 ? initialSkillId : null)
const acquiredLevelId = ref(null)
const argumentation = ref("")

const selectedAssignment = computed(() => context.value?.assignment || null)
const canRemove = computed(() => selectedAssignment.value?.canRemove === true)
const alreadyAcquired = computed(() => selectedAssignment.value?.acquired === true)
const argumentationLength = computed(() => argumentation.value.trim().length)
const argumentationInvalid = computed(
  () => Boolean(skillId.value) && !loading.value && !alreadyAcquired.value && argumentationLength.value < 10,
)
const argumentationLabel = computed(() => `${t("Argumentation")} * · ${t("minimum")} 10`)
const argumentationErrorText = computed(
  () => `${t("Required field")} · ${t("minimum")}: ${argumentationLength.value}/10`,
)

async function loadContext(selectedSkillId = null) {
  loading.value = true

  try {
    context.value = await skillService.getAssignmentContext(userId.value, selectedSkillId)

    const assignment = context.value?.assignment
    if (selectedSkillId && assignment) {
      argumentation.value = assignment.argumentation || ""
      acquiredLevelId.value = assignment.acquiredLevelId || null
    } else if (selectedSkillId) {
      argumentation.value = ""
      acquiredLevelId.value = null
    }
  } catch (error) {
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!skillId.value || argumentationInvalid.value || alreadyAcquired.value) {
    return
  }

  saving.value = true

  try {
    await skillService.updateAssignment(userId.value, {
      action: "assign",
      skillId: Number(skillId.value),
      acquiredLevelId: Number(acquiredLevelId.value || 0),
      argumentation: argumentation.value.trim(),
    })

    showSuccessNotification(t("Saved."))
    await loadContext(Number(skillId.value))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

function remove() {
  if (!canRemove.value || !skillId.value) {
    return
  }

  requireConfirmation({
    title: t("Remove"),
    message: t("Are you sure?"),
    async accept() {
      saving.value = true

      try {
        await skillService.updateAssignment(userId.value, {
          action: "remove",
          skillId: Number(skillId.value),
        })

        showSuccessNotification(t("Saved."))
        await loadContext(Number(skillId.value))
      } catch (error) {
        showErrorNotification(error)
      } finally {
        saving.value = false
      }
    },
  })
}

watch(
  skillId,
  async (value) => {
    if (!value) {
      argumentation.value = ""
      acquiredLevelId.value = null
      await loadContext()

      return
    }

    await loadContext(Number(value))
  },
  { immediate: true },
)

</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Assign skill')" />

    <BaseCard v-if="context?.user">
      <template #title>
        {{ context.user.fullName }}
      </template>

      <div class="flex flex-col gap-5">
        <BaseSelect
          id="skill-assignment-skill"
          v-model="skillId"
          :disabled="loading || saving"
          :label="t('Skill')"
          :options="context.skillOptions || []"
          allow-clear
          name="skillId"
          required
          show-required-marker
        />

        <div
          v-if="selectedAssignment?.acquired"
          class="rounded-lg border border-gray-25 bg-gray-10 p-4"
        >
          <div class="font-semibold">
            {{ t("Skill acquired") }}
          </div>
          <div v-if="selectedAssignment.sourceName">
            {{ selectedAssignment.sourceName }}
          </div>
        </div>

        <BaseSelect
          v-if="skillId && context.showLevels"
          id="skill-assignment-level"
          v-model="acquiredLevelId"
          :disabled="loading || saving || alreadyAcquired"
          :label="t('Level acquired')"
          :options="context.levelOptions || []"
          allow-clear
          name="acquiredLevelId"
        />

        <BaseTextArea
          v-if="skillId"
          id="skill-assignment-argumentation"
          v-model="argumentation"
          :disabled="loading || saving || alreadyAcquired"
          :error-text="argumentationErrorText"
          :is-invalid="argumentationInvalid"
          :label="argumentationLabel"
          name="argumentation"
          rows="6"
        />

        <div
          v-if="skillId"
          class="flex flex-wrap gap-3"
        >
          <BaseButton
            :disabled="loading || saving || alreadyAcquired || argumentationInvalid"
            :label="t('Save')"
            icon="check"
            type="primary"
            @click="save"
          />

          <BaseButton
            v-if="canRemove"
            :disabled="loading || saving"
            :label="t('Remove')"
            icon="delete"
            type="danger"
            @click="remove"
          />
        </div>
      </div>
    </BaseCard>
  </div>
</template>
