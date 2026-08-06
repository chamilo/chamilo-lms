/**
 * Double-submit CSRF token helper, shared by the Vue axios interceptor and the
 * legacy jQuery ajaxSetup so both speak the exact same protocol.
 *
 * Server side, CsrfProtectionListener validates every state-changing request to
 * an API Platform operation or a legacy AJAX endpoint through Symfony's
 * SameOriginCsrfTokenManager. That manager accepts a request when the origin
 * matches (Sec-Fetch-Site / Origin / Referer) or when a double-submit token is
 * present. This module produces the second one: a random token sent in the
 * "csrf-token" header, plus a cookie named "<prefix>csrf-token_<token>" holding
 * the literal value "csrf-token".
 *
 * A fresh token is issued per request because the manager clears the cookie on
 * every response it validates.
 */

const COOKIE_NAME = "csrf-token"

const HEADER_NAME = "csrf-token"

// Methods the listener skips, mirroring Symfony's Request::isMethodSafe().
const SAFE_METHODS = new Set(["get", "head", "options", "trace"])

// SameOriginCsrfTokenManager::TOKEN_MIN_LENGTH. 16 bytes give 32 hex chars.
const TOKEN_BYTES = 16

/**
 * Tells whether a request method requires a CSRF token.
 * @param {string} method HTTP method, in any case.
 * @returns {boolean}
 */
export function needsCsrfToken(method) {
  return !SAFE_METHODS.has(String(method || "get").toLowerCase())
}

/**
 * Generates a cryptographically random hexadecimal token.
 * @returns {string}
 */
function generateToken() {
  const bytes = new Uint8Array(TOKEN_BYTES)
  window.crypto.getRandomValues(bytes)

  return Array.from(bytes, (byte) => byte.toString(16).padStart(2, "0")).join("")
}

/**
 * Issues a token and stores it in the matching double-submit cookie.
 *
 * The "__Host-" prefix and the "secure" flag only apply over HTTPS, which is
 * the same condition SameOriginCsrfTokenManager applies when looking the cookie
 * up. "samesite=strict" keeps a cross-site page from having the cookie sent
 * along with a forged request.
 *
 * @returns {string} The token to put in the request header.
 */
export function issueCsrfToken() {
  const token = generateToken()
  const isSecure = "https:" === window.location.protocol
  const cookieName = `${isSecure ? "__Host-" : ""}${COOKIE_NAME}_${token}`

  document.cookie = `${cookieName}=${COOKIE_NAME}; path=/; samesite=strict${isSecure ? "; secure" : ""}`

  return token
}

/**
 * Name of the header the token travels in.
 * @returns {string}
 */
export function getCsrfHeaderName() {
  return HEADER_NAME
}
