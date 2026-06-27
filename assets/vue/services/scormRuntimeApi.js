const ERROR_MESSAGES_12 = {
  "0": "No error",
  "101": "General exception",
  "201": "Invalid argument error",
  "202": "Element cannot have children",
  "203": "Element not an array - cannot have count",
  "301": "Not initialized",
  "401": "Not implemented error",
  "402": "Invalid set value, element is a keyword",
  "403": "Element is read only",
  "404": "Element is write only",
  "405": "Incorrect data type",
}

const ERROR_MESSAGES_2004 = {
  "0": "No error",
  "101": "General exception",
  "102": "General initialization failure",
  "103": "Already initialized",
  "104": "Content instance terminated",
  "111": "General termination failure",
  "112": "Termination before initialization",
  "113": "Termination after termination",
  "122": "Retrieve data before initialization",
  "123": "Retrieve data after termination",
  "132": "Store data before initialization",
  "133": "Store data after termination",
  "142": "Commit before initialization",
  "143": "Commit after termination",
  "201": "General argument error",
  "301": "General get failure",
  "351": "General set failure",
  "391": "General commit failure",
  "401": "Undefined data model element",
  "402": "Unimplemented data model element",
  "403": "Data model element value not initialized",
  "404": "Data model element is read only",
  "405": "Data model element is write only",
  "406": "Data model element type mismatch",
  "407": "Data model element value out of range",
  "408": "Data model dependency not established",
}

const READ_ONLY_PREFIXES = [
  "cmi.core.student_id",
  "cmi.core.student_name",
  "cmi.core.total_time",
  "cmi.core.credit",
  "cmi.core.lesson_mode",
  "cmi.core.entry",
  "cmi.learner_id",
  "cmi.learner_name",
  "cmi.total_time",
  "cmi.credit",
  "cmi.mode",
  "cmi.entry",
  "cmi.launch_data",
  "cmi._version",
]

const WRITE_ONLY_ELEMENTS = new Set([
  "cmi.core.exit",
  "cmi.core.session_time",
  "cmi.exit",
  "cmi.session_time",
])

function isReadOnlyElement(name) {
  if (name.endsWith("._children") || name.endsWith("._count")) {
    return true
  }

  return READ_ONLY_PREFIXES.some((prefix) => name === prefix || name.startsWith(`${prefix}.`))
}

function isValidElementName(name) {
  return typeof name === "string" && /^cmi(?:\.|$)/.test(name)
}

function setDynamicCount(values, name) {
  const interactionMatch = name.match(/^cmi\.interactions\.(\d+)\./)
  if (interactionMatch) {
    const count = Math.max(Number(values["cmi.interactions._count"] || 0), Number(interactionMatch[1]) + 1)
    values["cmi.interactions._count"] = String(count)
  }

  const objectiveMatch = name.match(/^cmi\.objectives\.(\d+)\./)
  if (objectiveMatch) {
    const count = Math.max(Number(values["cmi.objectives._count"] || 0), Number(objectiveMatch[1]) + 1)
    values["cmi.objectives._count"] = String(count)
  }

  const responseMatch = name.match(/^cmi\.interactions\.(\d+)\.correct_responses\.(\d+)\./)
  if (responseMatch) {
    const countKey = `cmi.interactions.${responseMatch[1]}.correct_responses._count`
    const count = Math.max(Number(values[countKey] || 0), Number(responseMatch[2]) + 1)
    values[countKey] = String(count)
  }
}

export function createScormRuntimeApi({
  version,
  initialValues,
  forceCommit = false,
  debug = false,
  commit,
  beacon,
  onCommitted,
}) {
  const values = { ...(initialValues || {}) }
  const is2004 = String(version) === "2004"
  const errors = is2004 ? ERROR_MESSAGES_2004 : ERROR_MESSAGES_12
  let initialized = false
  let terminated = false
  let dirty = false
  let revision = 0
  let lastError = "0"
  let commitQueue = Promise.resolve()
  const changedRevisions = new Map()
  let forceCommitTimer = null

  const log = (...args) => {
    if (debug) {
      console.debug("[SCORM runtime]", ...args)
    }
  }

  const setError = (code) => {
    lastError = String(code)
    return false
  }

  const clearError = () => {
    lastError = "0"
  }

  const getErrorString = (code) => errors[String(code)] || "Unknown error"

  const buildPayload = (reason, terminatedValue = false) => ({
    values: { ...values },
    changedKeys: [...changedRevisions.keys()],
    terminated: terminatedValue,
    reason,
  })

  const queueCommit = (reason, terminatedValue = false) => {
    const payload = buildPayload(reason, terminatedValue)
    const committedRevision = revision
    const committedKeys = [...changedRevisions.keys()]
    commitQueue = commitQueue
      .catch(() => undefined)
      .then(async () => {
        await commit(payload)
        committedKeys.forEach((key) => {
          if (Number(changedRevisions.get(key) || 0) <= committedRevision) {
            changedRevisions.delete(key)
          }
        })
        dirty = changedRevisions.size > 0
        onCommitted?.()
      })
      .catch((error) => {
        dirty = true
        console.error("[SCORM runtime] Commit failed.", error)
      })

    return commitQueue
  }

  const scheduleForceCommit = () => {
    if (!forceCommit) {
      return
    }

    window.clearTimeout(forceCommitTimer)
    forceCommitTimer = window.setTimeout(() => {
      void queueCommit("force-commit")
    }, 150)
  }

  const initialize = (argument) => {
    if (argument !== "") {
      return setError(is2004 ? "201" : "201") ? "true" : "false"
    }
    if (terminated) {
      return setError(is2004 ? "104" : "101") ? "true" : "false"
    }
    if (initialized) {
      return setError(is2004 ? "103" : "101") ? "true" : "false"
    }

    initialized = true
    clearError()
    log("Initialize")

    return "true"
  }

  const terminate = (argument) => {
    if (argument !== "") {
      return setError("201") ? "true" : "false"
    }
    if (!initialized) {
      return setError(is2004 ? "112" : "301") ? "true" : "false"
    }
    if (terminated) {
      return setError(is2004 ? "113" : "101") ? "true" : "false"
    }

    terminated = true
    clearError()
    void queueCommit("terminate", true)
    log("Terminate")

    return "true"
  }

  const getValue = (name) => {
    if (!initialized) {
      setError(is2004 ? "122" : "301")
      return ""
    }
    if (terminated) {
      setError(is2004 ? "123" : "301")
      return ""
    }
    if (!isValidElementName(name)) {
      setError(is2004 ? "401" : "401")
      return ""
    }
    if (WRITE_ONLY_ELEMENTS.has(name)) {
      setError(is2004 ? "405" : "404")
      return ""
    }
    if (!Object.prototype.hasOwnProperty.call(values, name)) {
      setError(is2004 ? "401" : "401")
      return ""
    }

    clearError()
    const value = String(values[name] ?? "")
    log("GetValue", name, value)

    return value
  }

  const setValue = (name, value) => {
    if (!initialized) {
      return setError(is2004 ? "132" : "301") ? "true" : "false"
    }
    if (terminated) {
      return setError(is2004 ? "133" : "301") ? "true" : "false"
    }
    if (!isValidElementName(name)) {
      return setError(is2004 ? "401" : "401") ? "true" : "false"
    }
    if (isReadOnlyElement(name)) {
      return setError(is2004 ? "404" : "403") ? "true" : "false"
    }

    values[name] = String(value ?? "")
    setDynamicCount(values, name)
    revision += 1
    changedRevisions.set(name, revision)
    dirty = true
    clearError()
    scheduleForceCommit()
    log("SetValue", name, values[name])

    return "true"
  }

  const commitValues = (argument) => {
    if (argument !== "") {
      return setError("201") ? "true" : "false"
    }
    if (!initialized) {
      return setError(is2004 ? "142" : "301") ? "true" : "false"
    }
    if (terminated) {
      return setError(is2004 ? "143" : "301") ? "true" : "false"
    }

    clearError()
    void queueCommit("commit")
    log("Commit")

    return "true"
  }

  const getLastError = () => lastError
  const getErrorStringValue = (code) => getErrorString(code)
  const getDiagnostic = (code) => getErrorString(code || lastError)

  const api12 = {
    LMSInitialize: initialize,
    LMSFinish: terminate,
    LMSGetValue: getValue,
    LMSSetValue: setValue,
    LMSCommit: commitValues,
    LMSGetLastError: getLastError,
    LMSGetErrorString: getErrorStringValue,
    LMSGetDiagnostic: getDiagnostic,
  }

  const api2004 = {
    Initialize: initialize,
    Terminate: terminate,
    GetValue: getValue,
    SetValue: setValue,
    Commit: commitValues,
    GetLastError: getLastError,
    GetErrorString: getErrorStringValue,
    GetDiagnostic: getDiagnostic,
  }

  return {
    api12,
    api2004,
    async flush(reason = "flush") {
      window.clearTimeout(forceCommitTimer)
      if (!dirty && reason !== "navigation" && reason !== "unmount") {
        return commitQueue
      }

      return await queueCommit(reason, terminated)
    },
    flushBeacon(reason = "pagehide") {
      window.clearTimeout(forceCommitTimer)
      return beacon(buildPayload(reason, terminated))
    },
    destroy() {
      window.clearTimeout(forceCommitTimer)
    },
  }
}
