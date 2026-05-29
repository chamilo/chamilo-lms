import baseService from "./baseService"

const INSTALL_AJAX = "/main/inc/ajax/install.ajax.php"

export default {
  /**
   * Sends a test email during installation.
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  testMailer(formData) {
    return baseService.post(`${INSTALL_AJAX}?a=test_mailer`, formData)
  },

  /**
   * Sends the contact information collected during installation.
   * @param {FormData} formData
   * @returns {Promise<any>}
   */
  sendContactInformation(formData) {
    return baseService.post(`${INSTALL_AJAX}?a=send_contact_information`, formData, {
      "content-type": "application/x-www-form-urlencoded",
    })
  },
}
