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

export default {
  login,
  checkSession,
}
