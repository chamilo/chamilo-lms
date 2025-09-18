import baseService from "./baseService"

/**
 * @param {string} actionUrl
 * @param {Object} params
 * @param {string} params.login
 * @param {string} params.password
 * @param {boolean} params._remember_me
 * @param {string|null} [params.totp=null]
 * @returns {Promise<Object>}
 */
async function doLoginRequest(actionUrl, { login, password, _remember_me, totp = null }) {
  const payload = {
    username: login,
    password,
    _remember_me,
  }

  if (totp) {
    payload.totp = totp
  }

  return await baseService.post(actionUrl, payload)
}

/**
 * @param {Object} params
 * @param {string} params.login
 * @param {string} params.password
 * @param {boolean} params._remember_me
 * @param {string|null} [params.totp=null]
 * @returns {Promise<Object>}
 */
async function login({ login, password, _remember_me, totp = null }) {
  return await doLoginRequest("/login_json", { login, password, _remember_me, totp })
}

/**
 * @param {Object} params
 * @param {string} params.login
 * @param {string} params.password
 * @param {boolean} params._remember_me
 * @param {string|null} [params.totp=null]
 * @returns {Promise<Object>}
 */
async function loginLdap({ login, password, _remember_me, totp = null }) {
  return await doLoginRequest("/login/ldap/check", { login, password, _remember_me, totp })
}

/**
 * Checks the status of the user's session.
 * @returns {Promise<Object>}
 */
async function checkSession() {
  return await baseService.get("/check-session")
}

/**
 * @returns {Promise<string>}
 */
async function loginTokenRequest() {
  const { token } = await baseService.get(`/login/token/request`)

  return token
}

/**
 * @param {string} portalUrl
 * @param {string} token
 * @returns {Promise<void>}
 */
async function loginTokenCheck(portalUrl, token) {
  portalUrl = portalUrl.endsWith("/") ? portalUrl.slice(0, -1) : portalUrl

  await baseService.post(
    `${portalUrl}/login/token/check`,
    {},
    false,
    { Authorization: `Bearer ${token}` },
    { withCredentials: true },
  )
}

export default {
  login,
  loginLdap,
  checkSession,
  loginTokenRequest,
  loginTokenCheck,
}
