import makeService from "./api"

const legalExtensions = {
  async findAllAvailable() {
    try {
      const response = await fetch("/api/languages?available=true")
      if (!response.ok) {
        throw new Error("Network response was not ok")
      }
      return await response.json()
    } catch (error) {
      console.error("Error fetching available languages:", error)
      throw error
    }
  },
}
export default makeService("languages", legalExtensions)
