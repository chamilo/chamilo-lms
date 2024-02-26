import makeService from './api'
import { ENTRYPOINT } from "../config/entrypoint"

const legalExtensions = {
  async findAllAvailable() {
    const url = new URL(`${ENTRYPOINT}languages`)
    url.searchParams.append("available", "true")
    try {
      const response = await fetch(url.toString())
      if (!response.ok) {
        throw new Error('Network response was not ok')
      }
      return await response.json()
    } catch (error) {
      console.error('Error fetching available languages:', error)
      throw error
    }
  },
}
export default makeService('languages', legalExtensions)
