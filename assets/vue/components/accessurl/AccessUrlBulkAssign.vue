<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()

const props = defineProps({
  items: { type: Array, required: true }, // [{ id, label }]
  urls: { type: Array, required: true }, // [{ id, url }]
  itemsLabel: { type: String, required: true },
  addLabel: { type: String, required: true },
  removeLabel: { type: String, required: true },
  confirmAddMessage: { type: String, required: true },
  confirmRemoveMessage: { type: String, required: true },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(["submit"])

const selectedItemIds = ref([])
const selectedUrlIds = ref([])

function confirmSubmit(action) {
  if (0 === selectedItemIds.value.length || 0 === selectedUrlIds.value.length) {
    return
  }

  const message = "add" === action ? props.confirmAddMessage : props.confirmRemoveMessage

  requireConfirmation({
    message,
    accept: () => emit("submit", { itemIds: selectedItemIds.value, urlIds: selectedUrlIds.value, action }),
  })
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <BaseMultiSelect
        v-model="selectedItemIds"
        input-id="access-url-bulk-items"
        :label="itemsLabel"
        :options="items"
        option-label="label"
        option-value="id"
        filter
      />
      <BaseMultiSelect
        v-model="selectedUrlIds"
        input-id="access-url-bulk-urls"
        :label="t('URL list')"
        :options="urls"
        option-label="url"
        option-value="id"
        filter
      />
    </div>

    <div class="flex justify-center gap-4 flex-wrap">
      <BaseButton
        :label="addLabel"
        icon="plus"
        type="success"
        :disabled="disabled"
        @click="confirmSubmit('add')"
      />
      <BaseButton
        :label="removeLabel"
        icon="delete"
        type="danger"
        :disabled="disabled"
        @click="confirmSubmit('remove')"
      />
    </div>
  </div>
</template>
