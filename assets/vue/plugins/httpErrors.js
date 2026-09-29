import { notifySessionIssue } from "../composables/sessionNotice"
import { useUxStore } from "../store/uxStore"

/**
 * Reports denied (403) responses raised outside the Vue SPA's axios instance:
 * legacy jQuery/XHR code rendered inside the page and bare fetch() calls.
 *
 * The axios instance is deliberately not patched here. It is created at import
 * time (config/api.js), long before this runs, so patching axios.create() would
 * never reach it — and it does not need to: axios uses the XHR adapter in the
 * browser, so the XMLHttpRequest patch below already covers it.
 *
 * Must be installed once Pinia is active (see main.js): the denial is published
 * through the ux store.
 *
 * @returns {void}
 */
export default function installHttpErrors() {
  const setForbidden = (requestUrl) => {
    notifySessionIssue((text) => {
      useUxStore().showForbidden(text, requestUrl)
    })
  }

  // ---- 1) fetch ----
  try {
    if (window.fetch && !window.fetch.__httpErrorsPatched) {
      const _fetch = window.fetch.bind(window)
      window.fetch = async (...args) => {
        const res = await _fetch(...args)

        if (403 === res?.status) {
          setForbidden(res.url)
        }

        return res
      }
      window.fetch.__httpErrorsPatched = true
    }
  } catch (e) {
    console.warn("[httpErrors] fetch patch failed:", e?.message)
  }

  // ---- 2) XMLHttpRequest (covers legacy XHR and, through its adapter, axios) ----
  try {
    if (window.XMLHttpRequest && !window.XMLHttpRequest.__httpErrorsPatched) {
      const _open = XMLHttpRequest.prototype.open
      const _send = XMLHttpRequest.prototype.send

      XMLHttpRequest.prototype.open = function (method, url, ...rest) {
        this.__httpErrorsUrl = url

        return _open.call(this, method, url, ...rest)
      }

      XMLHttpRequest.prototype.send = function (...args) {
        this.addEventListener("readystatechange", function () {
          if (4 === this.readyState && 403 === this.status) {
            setForbidden(this.__httpErrorsUrl)
          }
        })

        return _send.apply(this, args)
      }

      window.XMLHttpRequest.__httpErrorsPatched = true
    }
  } catch (e) {
    console.warn("[httpErrors] xhr patch failed:", e?.message)
  }
}
