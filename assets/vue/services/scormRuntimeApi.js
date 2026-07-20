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

function getSetValueError(name, value, is2004) {
  if (!is2004) {
    return "0"
  }

  const normalizedValue = String(value ?? "").trim()
  if (name === "cmi.progress_measure" || name === "cmi.score.scaled") {
    if (normalizedValue === "" || !Number.isFinite(Number(normalizedValue))) {
      return "406"
    }

    const numericValue = Number(normalizedValue)
    const minimum = name === "cmi.progress_measure" ? 0 : -1
    if (numericValue < minimum || numericValue > 1) {
      return "407"
    }
  }

  return "0"
}

function createLegacyScormLogger(debug, itemId) {
  const logInLog = (message, priority) => {
    if (!debug) {
      return false
    }

    let color = "color: black"
    switch (priority) {
      case 0:
        color = "color:red;font-weight:bold"
        break
      case 1:
        color = "color:orange"
        break
      case 2:
        color = "color:green"
        break
      case 3:
        color = "color:blue"
        break
    }

    window.console.log(`%c${message}`, color)

    return false
  }

  return {
    logScorm(message, priority) {
      return logInLog(`SCORM: ${message}`, priority)
    },
    logLms(message, priority) {
      return logInLog(`LMS: ${message} (#lms_item_id = ${itemId})`, priority)
    },
  }
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
  lpId = 0,
  itemId = 0,
  itemViewId = 0,
  lpViewId = 0,
  userId = 0,
  lpType = 0,
  itemType = "",
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

  const { logScorm, logLms } = createLegacyScormLogger(debug, itemId)

  logLms("document.ready event starts")
  logLms(
    [
      "These logs are generated by the Learning Path SCORM runtime when the admin has clicked on the debug",
      'icon in the learning paths list: lines prefixed with "LMS:" refer to actions taken on the LMS side,',
      'while lines prefixed with "SCORM:" refer to actions taken to match the SCORM standard at the JS level.',
    ].join(" "),
    3,
  )
  logScorm("LMSSetValue calls are shown in red for better visibility.", 0)
  logScorm("Other SCORM calls are shown in orange.", 1)
  logLms("To add new messages to these logs, use logit_lms() or logit_scorm().")

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
    logLms(`function savedata(${itemId})`, 3)
    logLms("Ajax call", 3)
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
    logScorm("LMSInitialize()")

    if (argument !== "") {
      const code = "201"
      logScorm(`Error ${code}${getErrorString(code)}`, 0)
      return setError(code) ? "true" : "false"
    }
    if (terminated) {
      const code = is2004 ? "104" : "101"
      logScorm(`Error ${code}${getErrorString(code)}`, 0)
      return setError(code) ? "true" : "false"
    }
    if (initialized) {
      const code = is2004 ? "103" : "101"
      logScorm(`Error ${code}${getErrorString(code)}`, 0)
      return setError(code) ? "true" : "false"
    }

    initialized = true
    clearError()

    const status = values[is2004 ? "cmi.completion_status" : "cmi.core.lesson_status"] || ""
    const score = values[is2004 ? "cmi.score.raw" : "cmi.core.score.raw"] || ""
    const max = values[is2004 ? "cmi.score.max" : "cmi.core.score.max"] || ""
    const min = values[is2004 ? "cmi.score.min" : "cmi.core.score.min"] || ""
    const sessionTime = values[is2004 ? "cmi.session_time" : "cmi.core.session_time"] || ""
    const location = values[is2004 ? "cmi.location" : "cmi.core.lesson_location"] || ""
    const totalTime = values[is2004 ? "cmi.total_time" : "cmi.core.total_time"] || ""
    const masteryScore = values[is2004 ? "cmi.scaled_passing_score" : "cmi.student_data.mastery_score"] || ""
    const maxTimeAllowed = values["cmi.student_data.max_time_allowed"] || ""
    const credit = values[is2004 ? "cmi.credit" : "cmi.core.credit"] || ""
    const learnerId = values[is2004 ? "cmi.learner_id" : "cmi.core.student_id"] || ""
    const details =
      `\nitem             : ${itemId}` +
      `\nitem_type       : ${itemType}` +
      `\nscore           : ${score}` +
      `\nmax             : ${max}` +
      `\nmin             : ${min}` +
      `\nlesson_status   : ${status}` +
      `\nsession_time    : ${sessionTime}` +
      `\nlesson_location : ${location}` +
      `\nsuspend_data    : ${values["cmi.suspend_data"] || ""}` +
      `\ntotal_time      : ${totalTime}` +
      `\nmastery_score   : ${masteryScore}` +
      `\nmax_time_allowed: ${maxTimeAllowed}` +
      `\ncredit          : ${credit}` +
      `\nlms_lp_id       : ${lpId}` +
      `\nlms_user_id     : ${userId || learnerId}` +
      `\nlms_view_id     : ${lpViewId}` +
      `\nlms_item_view_id: ${itemViewId}` +
      `\nlms_lp_type     : ${lpType}`

    logScorm(`LMSInitialize() with params: ${details}`)

    return "true"
  }

  const terminate = (argument) => {
    if (!is2004 && !dirty) {
      logScorm("LMSFinish() (no LMSCommit())", 1)
    }
    logScorm(is2004 ? "Terminate()" : `LMSFinish() called on item ${itemId}`, 0)

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

    return "true"
  }

  const getValue = (name) => {
    if (!initialized) {
      const code = is2004 ? "122" : "301"
      setError(code)
      logScorm(
        `LMSGetValue(${name}) on item id ${itemId}:<br />=> Error ${code} ${getErrorString(code)}`,
      )
      return ""
    }
    if (terminated) {
      setError(is2004 ? "123" : "301")
      return ""
    }
    if (!isValidElementName(name)) {
      setError("401")
      logScorm(`LMSGetValue ('${name}') Error '${getErrorString("401")}'`, 1)
      return ""
    }
    if (WRITE_ONLY_ELEMENTS.has(name)) {
      setError(is2004 ? "405" : "404")
      return ""
    }
    if (!Object.prototype.hasOwnProperty.call(values, name)) {
      setError("401")
      logScorm(`LMSGetValue ('${name}') Error '${getErrorString("401")}'`, 1)
      return ""
    }

    clearError()
    const value = String(values[name] ?? "")
    logScorm(`LMSGetValue ('${name}') returned '${value}'`, 1)

    return value
  }

  const setValue = (name, value) => {
    logScorm(`LMSSetValue ('${name}','${value}')`, 0)
    logScorm(`Checking olms.lms_item_id ${itemId}`)

    if (!initialized) {
      return setError(is2004 ? "132" : "301") ? "true" : "false"
    }
    if (terminated) {
      return setError(is2004 ? "133" : "301") ? "true" : "false"
    }
    if (!isValidElementName(name)) {
      return setError("401") ? "true" : "false"
    }
    if (isReadOnlyElement(name)) {
      return setError(is2004 ? "404" : "403") ? "true" : "false"
    }

    const validationError = getSetValueError(name, value, is2004)
    if (validationError !== "0") {
      setError(validationError)
      return "false"
    }

    values[name] = String(value ?? "")
    setDynamicCount(values, name)
    revision += 1
    changedRevisions.set(name, revision)
    dirty = true
    clearError()
    scheduleForceCommit()

    return "true"
  }

  const commitValues = (argument) => {
    logScorm(`LMSCommit() val:${argument}`, 0)

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
    logScorm("LMSCommit() end ", 0)

    return "true"
  }

  const getLastError = () => {
    logScorm(`LMSGetLastError() returned: ${lastError}`, 1)

    return lastError
  }
  const getErrorStringValue = (code) => {
    logScorm("LMSGetErrorString()", 1)

    return getErrorString(code)
  }
  const getDiagnostic = (code) => {
    logScorm("LMSGetDiagnostic()", 1)

    return getErrorString(code || lastError)
  }

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
    logLms,
    logScorm,
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
