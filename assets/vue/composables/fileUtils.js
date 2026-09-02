export function looksLikeHtmlContent(content) {
  const value = String(content || "").trim()

  if (!value) {
    return false
  }

  if (/^<!doctype\s+html[\s>]/i.test(value) || /^<html[\s>]/i.test(value)) {
    return true
  }

  return /<(?:head|body|main|section|article|div|p|h[1-6]|table|thead|tbody|tr|td|ul|ol|li|figure|figcaption|style)\b[^>]*>/i.test(
    value,
  )
}

export function useFileUtils() {
  const isFile = (fileData) => {
    return !!fileData?.resourceNode?.firstResourceFile
  }

  const safeMime = (fileData) => {
    // Normalize MIME parameters such as "; charset=UTF-8".
    const raw = fileData?.resourceNode?.firstResourceFile?.mimeType || ""
    return String(raw).split(";")[0].trim()
  }

  const fileName = (fileData) => {
    return fileData?.resourceNode?.firstResourceFile?.originalName || fileData?.resourceNode?.title || ""
  }

  const ext = (fileData) => {
    const name = fileName(fileData)
    const match = /\.([A-Za-z0-9]+)$/.exec(name)
    return match ? match[1].toLowerCase() : ""
  }

  const isImage = (fileData) => {
    return isFile(fileData) && !!fileData.resourceNode.firstResourceFile.image
  }

  const isVideo = (fileData) => {
    return isFile(fileData) && !!fileData.resourceNode.firstResourceFile.video
  }

  const isAudio = (fileData) => {
    if (!isFile(fileData)) return false
    const top = safeMime(fileData).split("/")[0]?.toLowerCase() || ""
    return top === "audio" || !!fileData.resourceNode.firstResourceFile.audio
  }

  const isHtml = (fileData) => {
    if (!isFile(fileData)) return false

    const mime = safeMime(fileData).toLowerCase()
    const extension = ext(fileData)
    const byMime = mime.includes("text/html") || mime.includes("application/html") || mime.includes("application/xhtml")
    const byExt = extension === "html" || extension === "htm" || extension === "xhtml"

    return byMime || byExt
  }

  const isPreviewable = (fileData) => {
    return isImage(fileData) || isVideo(fileData) || isAudio(fileData) || isHtml(fileData)
  }

  return {
    isFile,
    isImage,
    isVideo,
    isAudio,
    isHtml,
    isPreviewable,
  }
}
