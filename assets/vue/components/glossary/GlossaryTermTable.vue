<template>
  <DataTable :value="glossaries">
    <Column
      :header="t('Term')"
      field="name"
    />
    <Column
      :header="t('Definition')"
      field="description"
    />
    <Column :header="t('Actions')">
      <template #body="{ data }">
        <BaseButton
          :label="t('Edit')"
          class="mr-2"
          icon="edit"
          size="small"
          type="black"
          @click="emit('edit', data)"
        />
        <BaseButton
          :label="t('Delete')"
          class="mr-2"
          icon="delete"
          size="small"
          type="danger"
          @click="emit('delete', data)"
        />
      </template>
    </Column>

    <template #empty>
      {{ t("There is no terms that matches the search: {searchTerm}", { searchTerm: searchTerm }) }}
    </template>
  </DataTable>
</template>

<script setup>
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

defineProps({
  glossaries: {
    type: Array,
    required: true,
  },
  searchTerm: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(["edit", "delete"])
</script>
