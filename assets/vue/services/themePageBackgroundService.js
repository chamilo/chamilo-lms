import baseService from "./baseService"

const url = (slug, slot = "") =>
  `/themes/${encodeURIComponent(slug)}/page-backgrounds${slot ? `/${encodeURIComponent(slot)}` : ""}`

async function list(slug) {
  return baseService.get(url(slug))
}

async function upload(slug, slot, file) {
  const data = new FormData()
  data.append("image", file)
  return baseService.post(url(slug, slot), data)
}

async function setHidden(slug, slot, hidden) {
  return baseService.patch(url(slug, slot), { hidden })
}

async function restoreDefault(slug, slot) {
  return baseService.delete(url(slug, slot))
}

export default { list, upload, setHidden, restoreDefault }
