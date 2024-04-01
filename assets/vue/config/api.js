import axios from "axios"

/**
 * @type {axios.AxiosInstance}
 */
const instance = axios.create({
  baseURL: window.location.origin,
  headers: {
    Accept: "application/ld+json",
  },
})

export default instance
