<template>
  <div class="overflow-x-auto pb-2">
    <div class="flex flex-nowrap items-start gap-4">
      <article
        v-for="boss in bosses"
        :key="boss.id"
        class="w-64 shrink-0 rounded-xl border border-gray-25 bg-gray-10 p-4"
        :data-boss-id="boss.id"
      >
        <h3 class="text-base font-semibold text-gray-90">{{ boss.fullName }}</h3>
        <p class="text-xs text-gray-50">{{ boss.username }}</p>

        <table class="mt-3 w-full text-sm">
          <thead>
            <tr class="border-b border-gray-25 text-gray-70">
              <th class="py-1 font-semibold">{{ t("Name") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!boss.learners?.length">
              <td class="py-2 text-gray-50">{{ t("No results found") }}</td>
            </tr>
            <tr
              v-for="learner in boss.learners"
              :key="learner.id"
              class="border-b border-gray-15 last:border-b-0"
            >
              <td class="py-2">
                <router-link
                  :to="learnerRoute(learner)"
                  class="text-primary hover:underline"
                >
                  {{ learner.fullName }}
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="canAddLearners"
          class="mt-4 space-y-3 border-t border-gray-25 pt-4"
        >
          <h4 class="text-sm font-semibold text-gray-90">{{ t("Add learner") }}</h4>
          <BaseAutocomplete
            :id="`student-boss-${boss.id}-learner`"
            v-model="selectedLearners[boss.id]"
            :label="t('Learner')"
            :search="globalReportingService.searchStudentBossLearners"
            option-label="label"
          />
          <div class="flex justify-end">
            <BaseButton
              :disabled="!selectedLearners[boss.id]?.id || savingBossId === boss.id"
              :is-loading="savingBossId === boss.id"
              :label="t('Add')"
              icon="check"
              type="success"
              @click="addLearner(boss)"
            />
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useNotification } from "../../composables/notification"
import globalReportingService from "../../services/globalReportingService"

const props = defineProps({
  bosses: {
    type: Array,
    required: true,
  },
  canAddLearners: {
    type: Boolean,
    default: false,
  },
  learnerRoute: {
    type: Function,
    required: true,
  },
})

const emit = defineEmits(["learner-added"])

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()

const selectedLearners = reactive({})
const savingBossId = ref(null)

watch(
  () => props.bosses,
  (bosses) => {
    for (const boss of bosses) {
      selectedLearners[boss.id] ??= ""
    }
  },
  { immediate: true },
)

async function addLearner(boss) {
  const learner = selectedLearners[boss.id]

  if (!learner?.id) {
    return
  }

  savingBossId.value = boss.id

  try {
    await globalReportingService.addLearnerToStudentBoss(boss.id, learner.id)
    showSuccessNotification(`${t("Saved")} ${learner.label}`)
    selectedLearners[boss.id] = ""
    emit("learner-added")
  } catch (error) {
    showErrorNotification(error)
  } finally {
    savingBossId.value = null
  }
}
</script>
