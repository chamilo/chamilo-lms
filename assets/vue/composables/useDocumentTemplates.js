import { ref } from "vue"
import { useRoute } from "vue-router"
import baseService from "../services/baseService"

/**
 * Composable for loading and inserting document templates.
 * @param {import('vue').Ref} itemRef - reactive document item with a `contentFile` property
 * @param {import('vue').Ref} formRef - ref to the DocumentsForm component
 */
export function useDocumentTemplates(itemRef, formRef) {
  const route = useRoute()
  const templates = ref([])

  async function fetchTemplates() {
    const courseId = Number(route.query.cid || 0)

    if (!courseId) {
      templates.value = []

      return
    }

    try {
      const response = await baseService.get(`/template/all-templates/${courseId}`)
      templates.value = Array.isArray(response) ? response : []
    } catch (error) {
      templates.value = []
      console.warn("[Documents] Failed to fetch templates. Continuing without document templates.", error)
    }
  }

  function addTemplateToEditor(templateContent) {
    if (formRef.value && typeof formRef.value.updateContent === "function") {
      formRef.value.updateContent(templateContent)

      return
    }

    itemRef.value.contentFile = templateContent
  }

  return {
    templates,
    fetchTemplates,
    addTemplateToEditor,
  }
}
