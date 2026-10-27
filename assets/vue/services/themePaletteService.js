import baseService from "./baseService"

const url = (slug) => `/themes/${encodeURIComponent(slug)}/palette`

async function status(slug) {
  return baseService.get(url(slug))
}

async function upload(slug, file) {
  const data = new FormData()
  data.append("palette", file)
  return baseService.post(url(slug), data)
}

async function remove(slug) {
  return baseService.delete(url(slug))
}

export default { status, upload, remove }
