import axios from "axios"

/**
 * @type {axios.AxiosInstance}
 */
const instance = axios.create({
  headers: {
    Accept: "application/ld+json",
  },
})

export default instance
