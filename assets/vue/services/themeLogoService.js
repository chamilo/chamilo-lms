async function upload(slug, { headerSvg, headerPng, emailSvg, emailPng }) {
  const fd = new FormData()
  if (headerSvg) fd.append("header_svg", headerSvg)
  if (headerPng) fd.append("header_png", headerPng)
  if (emailSvg) fd.append("email_svg", emailSvg)
  if (emailPng) fd.append("email_png", emailPng)

  const res = await fetch(`/themes/${encodeURIComponent(slug)}/logos`, {
    method: "POST",
    body: fd,
    credentials: "same-origin",
  })

  if (!res.ok) {
    const text = await res.text().catch(() => "")
    throw new Error(text || `Upload failed (${res.status})`)
  }
  return res.json()
}

async function remove(slug, type) {
  const res = await fetch(`/themes/${encodeURIComponent(slug)}/logos/${type}`, {
    method: "DELETE",
    credentials: "same-origin",
  })
  if (!res.ok) {
    const text = await res.text().catch(() => "")
    throw new Error(text || `Delete failed (${res.status})`)
  }
  return res.json()
}

export default { upload, remove }
