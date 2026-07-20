import baseService from "./baseService"

/**
 * @param {string} actionUrl
 * @param {Object} params
 * @param {string} params.login
 * @param {string} params.password
 * @param {boolean} params._remember_me
 * @param {string|null} [params.totp=null]
 * @param {string|null} [params.captcha_code=null]
 * @returns {Promise<Object>}
 */
async function doLoginRequest(actionUrl, { login, password, _remember_me, totp = null, captcha_code = null }) {
  const payload = {
    username: login,
    password,
    _remember_me,
  }

  if (totp) {
    payload.totp = totp
  }

  if (captcha_code) {
    payload.captcha_code = captcha_code
  }

  return await baseService.post(actionUrl, payload)
}

/**
 * @param {Object} params
 * @param {string} params.login
 * @param {string} params.password
 * @param {boolean} params._remember_me
 * @param {string|null} [params.totp=null]
 * @param {string|null} [params.captcha_code=null]
 * @returns {Promise<Object>}
 */
async function login({ login, password, _remember_me, totp = null, captcha_code = null }) {
  return await doLoginRequest("/login_json", {
    login,
    password,
    _remember_me,
    totp,
    captcha_code,
  })
}

/**
 * @param {Object} params
 * @param {string} params.login
 * @param {string} params.password
 * @param {boolean} params._remember_me
 * @param {string|null} [params.totp=null]
 * @param {string|null} [params.captcha_code=null]
 * @returns {Promise<Object>}
 */
async function loginLdap({ login, password, _remember_me, totp = null, captcha_code = null }) {
  return await doLoginRequest("/login/ldap/check", {
    login,
    password,
    _remember_me,
    totp,
    captcha_code,
  })
}

/**
 * @param {string} username
 * @returns {Promise<Object>}
 */
async function getLoginCaptchaStatus(username = "") {
  return await baseService.get(`/login/captcha/status?username=${encodeURIComponent(username)}`)
}

/**
 * Checks the status of the user's session.
 * @returns {Promise<Object>}
 */
async function checkSession() {
  return await baseService.get("/check-session")
}

/**
 * Fetches the contextual ROLE_CURRENT_COURSE_* roles the current user holds for
 * the given course context, as resolved authoritatively by the backend.
 * @param {Object} context
 * @param {number} context.cid - Current course id
 * @param {number} [context.sid=0] - Current session id
 * @param {number} [context.gid=0] - Current group id
 * @returns {Promise<string[]>}
 */
async function getCourseContextRoles({ cid, sid = 0, gid = 0 }) {
  const params = new URLSearchParams({ cid: String(cid) })

  if (sid) {
    params.set("sid", String(sid))
  }

  if (gid) {
    params.set("gid", String(gid))
  }

  const { roles } = await baseService.get(`/course-context-roles?${params.toString()}`)

  return roles ?? []
}

/**
 * Requests a login token from the server.
 * @returns {Promise<string>}
 */
async function loginTokenRequest() {
  const { token } = await baseService.get("/login/token/request")
  return token
}

/**
 * Checks the provided login token with the external portal.
 *
 * @param {string} portalUrl
 * @param {string} token
 * @returns {Promise<void>}
 */
async function loginTokenCheck(portalUrl, token) {
  portalUrl = portalUrl.endsWith("/") ? portalUrl.slice(0, -1) : portalUrl

  await baseService.post(
    `${portalUrl}/login/token/check`,
    {},
    { Authorization: `Bearer ${token}` },
    { withCredentials: true },
  )
}

export default {
  login,
  loginLdap,
  getLoginCaptchaStatus,
  checkSession,
  getCourseContextRoles,
  loginTokenRequest,
  loginTokenCheck,
}
