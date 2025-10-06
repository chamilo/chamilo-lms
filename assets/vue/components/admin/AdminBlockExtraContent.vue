<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import Inplace from "primevue/inplace"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import { useSecurityStore } from "../../store/securityStore"
import pageService from "../../services/pageService"
import BaseDivider from "../basecomponents/BaseDivider.vue"
import BaseButton from "../basecomponents/BaseButton.vue"

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

const newExtraContent = ref(modelExtraContent.value?.content)

async function saveExtraContent() {
  if (modelExtraContent.value["@id"]) {
    if (!newExtraContent.value) {
      await pageService.deletePage(modelExtraContent.value["@id"])

      modelExtraContent.value = {
        category: modelExtraContent.value.category,
      }

      return
    }

    const page = await pageService.updatePage(modelExtraContent.value["@id"], {
      content: newExtraContent.value,
    })

    modelExtraContent.value.content = page.content

    return
  }

  if (newExtraContent.value) {
    const page = await pageService.postPage({
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
        :title="t('Editable content')"
        align="right"
      />
      <div
        v-if="modelExtraContent?.content"
        class="text-body-2"
        v-html="modelExtraContent.content"
      />
    </template>
    <template #content="{ closeCallback }">
      <BaseTinyEditor
        v-model="newExtraContent"
        :editor-id="'new-description-editor' + id"
        :full-page="false"
      />
      <BaseButton
        :label="t('Save')"
        icon="save"
        size="small"
        type="black"
        @click="closeCallback"
      />
    </template>
  </Inplace>
  <div
    v-else-if="modelExtraContent?.content"
    class="text-body-2"
    v-html="modelExtraContent.content"
  />
</template>
