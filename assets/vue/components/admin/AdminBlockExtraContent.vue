<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import Inplace from "primevue/inplace"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import { useSecurityStore } from "../../store/securityStore"
import pageService from "../../services/pageService"
import BaseDivider from "../basecomponents/BaseDivider.vue"

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  editable: {
    type: Boolean,
    required: true,
  },
})

const modelExtraContent = defineModel({
  type: Object,
})

const securityStore = useSecurityStore()

const { t, locale } = useI18n()

const newExtraContent = ref(modelExtraContent.value.content)

async function saveExtraContent() {
  if (modelExtraContent.value["@id"]) {
    if (!newExtraContent.value) {
      await pageService.delete(modelExtraContent.value["@id"])

      modelExtraContent.value = {
        category: modelExtraContent.value.category,
      }

      return
    }

    const page = await pageService.update(modelExtraContent.value["@id"], {
      content: newExtraContent.value,
    })

    modelExtraContent.value.content = page.content

    return
  }

  if (newExtraContent.value) {
    const page = await pageService.post({
      title: props.id,
      content: newExtraContent.value,
      enabled: true,
      locale: locale.value,
      url: "/api/access_urls/" + window.access_url_id,
      creator: securityStore.user["@id"],
      category: modelExtraContent.value.category,
    })

    modelExtraContent.value = {
      "@id": page["@id"],
      content: page.content,
      category: page.category["@id"],
    }
  }
}
</script>

<template>
  <Inplace
    v-if="editable"
    :closable="true"
    @close="saveExtraContent"
  >
    <template #display>
      <BaseDivider
        align="right"
        :title="t('Editable content')"
      />
      <div
        v-if="modelExtraContent.content"
        class="text-body-2"
        v-html="modelExtraContent.content"
      />
    </template>
    <template #content>
      <BaseTinyEditor
        v-model="newExtraContent"
        :editor-id="'new-description-editor' + id"
        :full-page="false"
      />
    </template>
  </Inplace>
  <div
    v-else-if="modelExtraContent.content"
    class="text-body-2"
    v-html="modelExtraContent.content"
  />
</template>
