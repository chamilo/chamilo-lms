import { useNotification } from "./notification"

export const CERTIFICATE_TAGS = [
  "((user_firstname))",
  "((user_lastname))",
  "((user_username))",
  "((gradebook_institution))",
  "((gradebook_sitename))",
  "((teacher_firstname))",
  "((teacher_lastname))",
  "((official_code))",
  "((date_certificate))",
  "((date_certificate_no_time))",
  "((course_code))",
  "((course_title))",
  "((gradebook_grade))",
  "((certificate_link))",
  "((certificate_link_html))",
  "((certificate_barcode))",
  "((external_style))",
  "((time_in_course))",
  "((time_in_course_in_all_sessions))",
  "((start_date_and_end_date))",
  "((course_objectives))",
]

/**
 * Composable for the certificate tags panel.
 * @param {import('vue').Ref} itemRef - reactive document item with a `contentFile` property
 */
export function useCertificateTags(itemRef) {
  const { showSuccessNotification, showErrorNotification } = useNotification()

  function insertIntoEditor(text) {
    try {
      if (window.tinymce) {
        const editor = window.tinymce.get("item_content") || window.tinymce.activeEditor

        if (editor) {
          editor.focus()
          editor.selection.setContent(text)

          return true
        }
      }
    } catch (e) {
      console.warn("[Certificate] Failed to insert into TinyMCE editor:", e)
    }

    itemRef.value.contentFile = String(itemRef.value.contentFile || "") + text

    return false
  }

  async function writeToClipboard(text) {
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text)

        return true
      }
    } catch (e) {
      console.warn("[Certificate] Clipboard API failed, using fallback:", e)
    }

    try {
      const textarea = document.createElement("textarea")
      textarea.value = text
      textarea.setAttribute("readonly", "")
      textarea.style.position = "fixed"
      textarea.style.top = "-1000px"
      textarea.style.left = "-1000px"
      textarea.style.opacity = "0"
      document.body.appendChild(textarea)
      textarea.focus()
      textarea.select()

      const ok = document.execCommand("copy")
      document.body.removeChild(textarea)

      return ok
    } catch (e) {
      console.warn("[Certificate] Clipboard fallback failed:", e)

      return false
    }
  }

  async function insertCertificateTag(tag) {
    insertIntoEditor(tag)
    await writeToClipboard(tag)
  }

  async function copyAllCertificateTags() {
    const text = CERTIFICATE_TAGS.join("\n")
    const ok = await writeToClipboard(text)

    if (ok) {
      showSuccessNotification("All tags copied to clipboard")

      return
    }

    showErrorNotification("Failed to copy tags")
  }

  return {
    certificateTags: CERTIFICATE_TAGS,
    insertCertificateTag,
    copyAllCertificateTags,
  }
}
