<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const props = defineProps({
  configuration: { type: Object, required: true },
  context: { type: Object, required: true },
  csrfToken: { type: String, required: true },
  items: { type: Array, required: true },
  lpId: { type: Number, required: true },
})

const emit = defineEmits(["saved"])
const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()

const saving = ref(false)
const removeAuthors = ref(false)
const price = ref(null)
const selectedItems = reactive({})
const selectedAuthors = reactive({})

const flatItems = computed(() => {
  const result = []

  function append(items, depth = 0) {
    ;(items || []).forEach((item) => {
      result.push({ ...item, depth })
      append(item.children || [], depth + 1)
    })
  }

  append(props.items)

  return result
})

const selectedItemIds = computed(() =>
  flatItems.value.filter((item) => Boolean(selectedItems[item.id])).map((item) => Number(item.id)),
)
const selectedAuthorIds = computed(() =>
  (props.configuration.authors || [])
    .filter((author) => Boolean(selectedAuthors[author.value]))
    .map((author) => Number(author.value)),
)
const allItemsSelected = computed({
  get: () => flatItems.value.length > 0 && selectedItemIds.value.length === flatItems.value.length,
  set: (selected) => {
    flatItems.value.forEach((item) => {
      selectedItems[item.id] = Boolean(selected)
    })
  },
})
const hasOperation = computed(
  () => removeAuthors.value || selectedAuthorIds.value.length > 0 || Number(price.value || 0) > 0,
)
const canSubmit = computed(() => selectedItemIds.value.length > 0 && hasOperation.value)
const removeAuthorLabel = computed(() => `${t("Remove")} ${t("Author")}`)

watch(
  () => props.configuration,
  () => resetForm(),
  { immediate: true, deep: true },
)

function resetForm() {
  Object.keys(selectedItems).forEach((key) => delete selectedItems[key])
  Object.keys(selectedAuthors).forEach((key) => delete selectedAuthors[key])
  removeAuthors.value = false
  price.value = null
}

function itemLabel(item) {
  return `${"— ".repeat(Math.min(Number(item.depth || 0), 6))}${item.displayTitle || item.title}`
}

function getItemValue(itemId) {
  return props.configuration.values?.[itemId] || {
    authorIds: [],
    authorNames: [],
    price: "",
  }
}

async function save() {
  if (!canSubmit.value) {
    return
  }

  saving.value = true
  try {
    await lpService.updateBuilderBulkAuthorPrice(props.lpId, props.context, {
      itemIds: selectedItemIds.value,
      authorIds: removeAuthors.value ? [] : selectedAuthorIds.value,
      removeAuthors: removeAuthors.value,
      price: Number(price.value || 0) > 0 ? Number(price.value) : null,
      csrfToken: props.csrfToken,
    })
    showSuccessNotification(t("Updated"))
    emit("saved")
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
      {{ t("Author") }} / {{ t("Price") }}
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(280px,36%)]">
      <div class="rounded-lg border border-gray-20 p-4">
        <div class="mb-3 flex items-center justify-between gap-2 border-b border-gray-20 pb-3">
          <div class="font-semibold text-gray-90">
            {{ t("Select items") }}
          </div>
          <BaseCheckbox
            id="lp-bulk-author-price-select-all"
            v-model="allItemsSelected"
            :label="t('Select all')"
            name="selectAllItems"
          />
        </div>

        <div
          v-if="flatItems.length"
          class="max-h-[32rem] space-y-2 overflow-y-auto pr-1"
        >
          <div
            v-for="item in flatItems"
            :key="item.id"
            class="rounded-lg border border-gray-20 px-3 py-2"
          >
            <BaseCheckbox
              :id="`lp-bulk-author-price-item-${item.id}`"
              v-model="selectedItems[item.id]"
              :label="itemLabel(item)"
              :name="`item_${item.id}`"
            />
            <div
              v-if="getItemValue(item.id).authorNames?.length || getItemValue(item.id).price"
              class="ml-7 mt-1 space-y-1 text-caption text-gray-50"
            >
              <div v-if="getItemValue(item.id).authorNames?.length">
                {{ t("Author") }}: {{ getItemValue(item.id).authorNames.join(", ") }}
              </div>
              <div v-if="getItemValue(item.id).price">
                {{ t("Price") }}: {{ getItemValue(item.id).price }}
              </div>
            </div>
          </div>
        </div>
        <div
          v-else
          class="py-6 text-center text-gray-50"
        >
          {{ t("No data available") }}
        </div>
      </div>

      <div class="space-y-4">
        <div
          v-if="configuration.authorsEnabled"
          class="rounded-lg border border-gray-20 p-4"
        >
          <div class="mb-3 font-semibold text-gray-90">
            {{ t("Author") }}
          </div>

          <BaseCheckbox
            id="lp-bulk-author-price-remove-authors"
            v-model="removeAuthors"
            :label="removeAuthorLabel"
            name="removeAuthors"
          />

          <div
            v-if="configuration.authors?.length"
            class="mt-3 max-h-64 space-y-2 overflow-y-auto border-t border-gray-20 pt-3"
          >
            <BaseCheckbox
              v-for="author in configuration.authors"
              :id="`lp-bulk-author-${author.value}`"
              :key="author.value"
              v-model="selectedAuthors[author.value]"
              :disabled="removeAuthors"
              :label="author.label"
              :name="`author_${author.value}`"
            />
          </div>
          <div
            v-else
            class="mt-3 border-t border-gray-20 pt-3 text-caption text-gray-50"
          >
            {{ t("No data available") }}
          </div>
        </div>

        <div
          v-if="configuration.priceEnabled"
          class="rounded-lg border border-gray-20 p-4"
        >
          <BaseInputNumber
            id="lp-bulk-author-price-value"
            v-model="price"
            :label="t('Price')"
            :min="0"
            name="price"
          />
        </div>

        <div class="flex justify-end">
          <BaseButton
            :disabled="!canSubmit"
            :is-loading="saving"
            :label="t('Send')"
            icon="save"
            type="success"
            @click="save"
          />
        </div>
      </div>
    </div>
  </div>
</template>
