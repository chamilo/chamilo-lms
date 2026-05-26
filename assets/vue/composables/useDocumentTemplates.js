import { ref } from "vue"
import { useRoute } from "vue-router"
import documentsService from "../services/documents"

/**
 * Composable for loading and inserting document templates.
 * @param {import('vue').Ref} itemRef - reactive document item with a `contentFile` property
 * @param {import('vue').Ref} formRef - ref to the DocumentsForm component
 */
export function useDocumentTemplates(itemRef, formRef) {
  const route = useRoute()
  const templates = ref([])

  async function fetchTemplates() {
    const courseId = route.query.cid

    try {
      templates.value = await documentsService.getTemplates(courseId)
    } catch (e) {
      console.error("[Documents] Failed to fetch templates:", e)
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
