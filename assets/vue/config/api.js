import axios from "axios"

const instance = axios.create({
  baseURL: window.location.origin,
  headers: {
    'Accept': 'application/ld+json',
  },
})

export default instance
