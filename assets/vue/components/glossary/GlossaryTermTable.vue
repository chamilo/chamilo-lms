<template>
  <BaseTable
    :text-for-empty="t('There are no terms that match the search: {0}', [searchTerm])"
    :values="glossaries"
    :total-items="glossaries.length"
    data-key="title"
  >
    <Column
      :header="t('Term')"
      field="title"
    />

    <Column :header="t('Definition')">
      <template #body="{ data }">
        <div
          class="prose max-w-none"
          v-html="sanitize(data.description)"
        ></div>
      </template>
    </Column>

    <Column
      v-if="props.canEditGlossary"
      :header="t('Actions')"
    >
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
  </BaseTable>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import Column from "primevue/column"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import DOMPurify from "dompurify"

const { t } = useI18n()

const props = defineProps({
  glossaries: {
    type: Array,
    required: true,
  },
  searchTerm: {
    type: String,
    required: true,
  },
  canEditGlossary: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(["edit", "delete"])
const sanitize = (html) => DOMPurify.sanitize(html ?? "", { ADD_ATTR: ["target", "rel"] })
</script>
