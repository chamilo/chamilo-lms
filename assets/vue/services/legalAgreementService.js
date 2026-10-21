import baseService from "./baseService"

export default {
  /**
   * Sends a learner their terms and conditions, or deletes their acceptance, through
   * LegalAgreementController.
   *
   * @param {string} actionUrl /legal-agreement/{userId}/send or /legal-agreement/{userId}/delete
   * @param {string} csrfToken
   * @returns {Promise<{success: boolean, message: string}>}
   */
  async run(actionUrl, csrfToken) {
    return await baseService.post(actionUrl, { _token: csrfToken })
  },
}
