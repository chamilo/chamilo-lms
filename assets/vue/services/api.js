import fetch from "../utils/fetch"

// As stated here https://github.com/chamilo/chamilo-lms/pull/5386#discussion_r1578471409
// this service should not be used and instead the assets/bue/config/api.js should be used instead
// take a look at assets/bue/services/socialService.js to have an example
export default function makeService(endpoint, extensions = {}) {
  const baseService = {
    find(id, params) {
      console.log("api.js find")
      const currentParams = new URLSearchParams(window.location.search)
      const combinedParams = {
        ...Object.fromEntries(currentParams),
        ...params,
        getFile: true,
      }

      const searchParams = new URLSearchParams(combinedParams)

      return fetch(id, { params: Object.fromEntries(searchParams) })
    },
    findAll(params) {
      console.log("api.js findAll")
      console.log(params)
      return fetch(endpoint, params)
    },
    async createWithFormData(payload) {
      console.log("api.js createWithFormData")

      let formData = new FormData()
      console.log("body")
      console.log(payload)
      if (payload) {
        Object.keys(payload).forEach(function (key) {
          // key: the name of the object key
          // index: the ordinal position of the key within the object
          formData.append(key, payload[key])
          console.log("options.key", key)
        })
        payload = formData
      }

      return fetch(endpoint, { method: "POST", body: payload })
    },
    async create(payload) {
      console.log("api.js create")
      console.log(payload)
      return fetch(endpoint, { method: "POST", body: JSON.stringify(payload) })
    },
    del(item) {
      console.log("api.js del")
      console.log(item["@id"])
      return fetch(item["@id"], { method: "DELETE" })
    },
    updateWithFormData(payload) {
      console.log("api.js - update")

      return fetch(payload["@id"], {
        method: "PUT",
        body: JSON.stringify(payload),
      })
    },
    update(payload) {
      console.log("api.js - update")

      return fetch(payload["@id"], {
        method: "PUT",
        body: JSON.stringify(payload),
      })
    },
    handleError(error, errorsRef, violationsRef) {
      if (error instanceof SubmissionError) {
        violationsRef.value = error.errors
        errorsRef.value = error.errors._error
        return
      }
      errorsRef.value = error.message
    },
  }

  return { ...baseService, ...extensions }
}
