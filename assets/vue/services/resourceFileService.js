import baseService from "./baseService"

const endpoint = "/api/resource_files"

const post = async (formData) => {
  return await baseService.postForm(endpoint, formData)
}

export default {
  endpoint,
  post,
}
