import baseService from "./baseService"

/**
 * @param {string} login
 * @param {string} password
 * @param {boolean} _remember_me
 * @returns {Promise<Object>}
 */
async function login({ login, password, _remember_me, totp = null }) {
  const payload = {
    username: login,
    password,
    _remember_me,
  }

  if (totp) {
    payload.totp = totp
  }

  return await baseService.post("/login_json", payload)
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
  checkSession,
  loginTokenRequest,
  loginTokenCheck,
}
