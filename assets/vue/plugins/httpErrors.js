export default function installHttpErrors({ store, on401, on403, on500, t } = {}) {
  const getDefault403 = () => t("You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.")

  const resolve403Text = (msg) => (msg && typeof msg === "string" ? msg : getDefault403())

  const setForbidden = (msg) => {
    const text = resolve403Text(msg)
    try {
      console.log("[httpErrors] 403 captured ->", text)
      store?.dispatch?.("ux/showForbidden", text)
    } catch (e) {
      console.warn("[httpErrors] cannot dispatch ux/showForbidden:", e)
    }
    try {
      on403?.(text)
    } catch {}
  }

  const isHtmlResponse = (res) => {
    try {
      const ct = res?.headers?.get?.("content-type") || res?.headers?.["content-type"] || ""
      return typeof ct === "string" && ct.toLowerCase().includes("text/html")
    } catch {
      return false
    }
  }

  const isErrorPageHeader = (res) => {
    try {
      const v = res?.headers?.get?.("x-error-page") || res?.headers?.["x-error-page"] || ""
      return String(v) === "1"
    } catch {
      return false
    }
  }

  const replaceWholeDocument = (html) => {
    // Replace the entire DOM with the Twig/HTML page returned by the backend
    document.open()
    document.write(html)
    document.close()
  }

  const extractMsgFromJson = (json) => json?.error || json?.message || json?.detail || null

  // ---- 1) Axios (default + any instances created later) ----
  try {
    const axios = require("axios").default || require("axios")

    const axiosReject = async (err) => {
      const res = err?.response
      const s = res?.status

      if (s === 403) {
        const looksHtml = isHtmlResponse(res)
        const flagged = isErrorPageHeader(res)
        const body = res?.data

        if (looksHtml && flagged && typeof body === "string") {
          // Backend returned the full error page (HTML/Twig): replace the document
          replaceWholeDocument(body)
          return Promise.reject(err)
        }

        // Fallback to JSON -> show Vue banner
        setForbidden(extractMsgFromJson(body))
        return Promise.reject(err)
      }

      if (s === 401) {
        try {
          on401?.(err)
        } catch {}
      } else if (s === 500) {
        try {
          on500?.(err)
        } catch {}
      }
      return Promise.reject(err)
    }

    axios.interceptors.response.use((r) => r, axiosReject)

    const originalCreate = axios.create.bind(axios)
    axios.create = function patchedCreate(...args) {
      const instance = originalCreate(...args)
      instance.interceptors.response.use((r) => r, axiosReject)
      return instance
    }

    // Soft hint header in case the backend wants to send HTML for XHR errors
    try {
      axios.defaults.headers.common["X-Prefer-HTML-Errors"] = "1"
    } catch {}
    console.log("[httpErrors] axios patched")
  } catch (e) {
    console.warn("[httpErrors] axios not available (skipping)", e?.message)
  }

  // ---- 2) fetch ----
  try {
    if (window.fetch && !window.fetch.__httpErrorsPatched) {
      const _fetch = window.fetch.bind(window)
      window.fetch = async (...args) => {
        const res = await _fetch(...args)
        if (res?.status === 403) {
          if (isHtmlResponse(res) && isErrorPageHeader(res)) {
            const html = await res
              .clone()
              .text()
              .catch(() => "")
            if (html) replaceWholeDocument(html)
          } else {
            const ct = res.headers.get("content-type") || ""
            if (ct.includes("json")) {
              const data = await res
                .clone()
                .json()
                .catch(() => ({}))
              setForbidden(extractMsgFromJson(data))
            } else {
              setForbidden(null) // translated default
            }
          }
        }
        if (res?.status === 401) {
          try {
            on401?.(res)
          } catch {}
        }
        if (res?.status === 500) {
          try {
            on500?.(res)
          } catch {}
        }
        return res
      }
      window.fetch.__httpErrorsPatched = true
      console.log("[httpErrors] fetch patched")
    }
  } catch (e) {
    console.warn("[httpErrors] fetch patch failed:", e?.message)
  }

  // ---- 3) XMLHttpRequest (covers manual XHR usage) ----
  try {
    if (window.XMLHttpRequest && !window.XMLHttpRequest.__httpErrorsPatched) {
      const _open = XMLHttpRequest.prototype.open
      const _send = XMLHttpRequest.prototype.send

      XMLHttpRequest.prototype.open = function (method, url, ...rest) {
        this.__httpErrorsUrl = url
        this.__httpErrorsMethod = method
        return _open.call(this, method, url, ...rest)
      }

      XMLHttpRequest.prototype.send = function (...args) {
        this.addEventListener("readystatechange", function () {
          if (this.readyState === 4) {
            const s = this.status
            if (s === 403) {
              const ct = (this.getResponseHeader("content-type") || "").toLowerCase()
              const flagged = this.getResponseHeader("x-error-page") === "1"

              if (ct.includes("text/html") && flagged) {
                replaceWholeDocument(this.responseText || "")
                return
              }

              let msg = null
              if (ct.includes("json")) {
                try {
                  const parsed = JSON.parse(this.responseText || "{}")
                  msg = extractMsgFromJson(parsed) // may be null -> we’ll translate later
                } catch {}
              }
              setForbidden(msg) // translated default if null
            } else if (s === 401) {
              try {
                on401?.(this)
              } catch {}
            } else if (s === 500) {
              try {
                on500?.(this)
              } catch {}
            }
          }
        })
        return _send.apply(this, args)
      }

      window.XMLHttpRequest.__httpErrorsPatched = true
      console.log("[httpErrors] xhr patched")
    }
  } catch (e) {
    console.warn("[httpErrors] xhr patch failed:", e?.message)
  }
}
