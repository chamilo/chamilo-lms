import { defineStore } from "pinia"
import { ref } from "vue"

export const useUxStore = defineStore("ux", () => {
  const forbiddenMessage = ref("")
  // URL of the request that was denied. Lets the UI tell a denial of the page
  // the user is looking at from one raised by a background widget.
  const forbiddenRequestUrl = ref("")

  /**
   * Publishes a denied (403) request so the layout can warn about it.
   * @param {string} message - Message to display.
   * @param {string} [requestUrl] - URL of the denied request.
   * @returns {void}
   */
  function showForbidden(message, requestUrl) {
    forbiddenMessage.value = message || ""
    forbiddenRequestUrl.value = requestUrl || ""
  }

  /**
   * Drops the current denial, so the banner and the breadcrumb recover.
   * @returns {void}
   */
  function clearForbidden() {
    forbiddenMessage.value = ""
    forbiddenRequestUrl.value = ""
  }

  return {
    forbiddenMessage,
    forbiddenRequestUrl,
    showForbidden,
    clearForbidden,
  }
})
