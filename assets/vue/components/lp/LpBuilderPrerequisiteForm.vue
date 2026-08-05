<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import RadioButton from "primevue/radiobutton"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const props = defineProps({
  context: { type: Object, required: true },
  csrfToken: { type: String, required: true },
  item: { type: Object, required: true },
  items: { type: Array, required: true },
  lpId: { type: Number, required: true },
})

const emit = defineEmits(["saved"])
const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()

const saving = ref(false)
const prerequisiteId = ref(0)
const scoreValues = reactive({})

const candidates = computed(() => {
  const result = []

  function append(items, depth = 0) {
    ;(items || []).forEach((candidate) => {
      if (Number(candidate.id) !== Number(props.item.id)
        && Number(candidate.displayOrder) <= Number(props.item.displayOrder)
      ) {
        result.push({ ...candidate, depth })
      }
      append(candidate.children || [], depth + 1)
    })
  }

  append(props.items)

  return result
})

watch(
  () => [props.item, candidates.value],
  () => {
    prerequisiteId.value = Number(props.item?.prerequisiteId || 0)

    candidates.value.forEach((candidate) => {
      const selected = Number(candidate.id) === prerequisiteId.value
      scoreValues[candidate.id] = {
        min: selected ? Number(props.item?.prerequisiteMinScore || 0) : Number(candidate.masteryScore || 0),
        max: selected
          ? Number(props.item?.prerequisiteMaxScore || candidate.maxScore || 100)
          : Number(candidate.maxScore || 100),
      }
    })
  },
  { immediate: true, deep: true },
)

function isScored(candidate) {
  return ["quiz", "hotpotatoes"].includes(String(candidate?.itemType || ""))
}

async function savePrerequisite() {
  const selected = candidates.value.find((candidate) => Number(candidate.id) === Number(prerequisiteId.value))
  const scores = selected && isScored(selected)
    ? scoreValues[selected.id] || { min: 0, max: Number(selected.maxScore || 100) }
    : { min: 0, max: 100 }

  saving.value = true
  try {
    await lpService.updateBuilderItemPrerequisite(props.lpId, Number(props.item.id), props.context, {
      prerequisiteId: Number(prerequisiteId.value || 0),
      minScore: Number(scores.min || 0),
      maxScore: Number(scores.max || 100),
      csrfToken: props.csrfToken,
    })
    showSuccessNotification(t("Update successful"))
    emit("saved", Number(props.item.id))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="text-h4 font-semibold text-gray-90">
      {{ t("Add/edit prerequisites") }} {{ item.displayTitle || item.title }}
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-25 bg-white">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-20">
          <thead class="bg-gray-10">
            <tr>
              <th class="px-4 py-3 text-left text-caption font-semibold uppercase text-gray-70">
                {{ t("Prerequisites") }}
              </th>
              <th class="w-36 px-4 py-3 text-left text-caption font-semibold uppercase text-gray-70">
                {{ t("minimum") }}
              </th>
              <th class="w-36 px-4 py-3 text-left text-caption font-semibold uppercase text-gray-70">
                {{ t("maximum") }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-15">
            <tr>
              <td
                class="px-4 py-3"
                colspan="3"
              >
                <label class="flex cursor-pointer items-center gap-2">
                  <RadioButton
                    v-model="prerequisiteId"
                    input-id="lp-prerequisite-none"
                    name="prerequisiteId"
                    :value="0"
                  />
                  <span>{{ t("none") }}</span>
                </label>
              </td>
            </tr>

            <tr
              v-for="candidate in candidates"
              :key="candidate.id"
              :class="candidate.isSection ? 'opacity-60' : 'hover:bg-gray-10'"
            >
              <td
                class="px-4 py-3"
                :colspan="isScored(candidate) ? 1 : 3"
              >
                <label
                  class="flex items-center gap-2"
                  :class="candidate.isSection ? 'cursor-not-allowed' : 'cursor-pointer'"
                  :style="{ paddingLeft: `${candidate.depth * 20}px` }"
                >
                  <RadioButton
                    v-model="prerequisiteId"
                    :disabled="candidate.isSection"
                    :input-id="`lp-prerequisite-${candidate.id}`"
                    name="prerequisiteId"
                    :value="Number(candidate.id)"
                  />
                  <BaseIcon
                    :icon="candidate.isSection ? 'folder-generic' : 'file-text'"
                    size="small"
                  />
                  <span>{{ candidate.displayTitle || candidate.title }}</span>
                </label>
              </td>

              <template v-if="isScored(candidate)">
                <td class="px-4 py-3">
                  <BaseInputNumber
                    :id="`lp-prerequisite-min-${candidate.id}`"
                    v-model="scoreValues[candidate.id].min"
                    :disabled="Number(prerequisiteId) !== Number(candidate.id)"
                    :label="t('minimum')"
                    :max="Number(candidate.maxScore || 0) > 0 ? Number(candidate.maxScore) : undefined"
                    :min="0"
                    :step="0.01"
                  />
                </td>
                <td class="px-4 py-3">
                  <BaseInputNumber
                    :id="`lp-prerequisite-max-${candidate.id}`"
                    v-model="scoreValues[candidate.id].max"
                    :disabled="Number(prerequisiteId) !== Number(candidate.id)"
                    :label="t('maximum')"
                    :max="Number(candidate.maxScore || 0) > 0 ? Number(candidate.maxScore) : undefined"
                    :min="0"
                    :step="0.01"
                  />
                </td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-end border-t border-gray-20 bg-gray-10 px-4 py-3">
        <BaseButton
          :is-loading="saving"
          :label="t('Save prerequisites settings')"
          icon="save"
          type="primary"
          @click="savePrerequisite"
        />
      </div>
    </div>
  </div>
</template>
