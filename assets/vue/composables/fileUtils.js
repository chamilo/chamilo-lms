export function useFileUtils() {
  const isFile = (fileData) => {
    return !!fileData?.resourceNode?.firstResourceFile
  }

  const safeMime = (fileData) => {
    // normalize: strip params like "; charset=UTF-8"
    const raw = fileData?.resourceNode?.firstResourceFile?.mimeType || ""
    return String(raw).split(";")[0].trim()
  }

  const fileName = (fileData) => {
    // prefer originalName; fallback to node title
    return fileData?.resourceNode?.firstResourceFile?.originalName || fileData?.resourceNode?.title || ""
  }

  const ext = (fileData) => {
    const name = fileName(fileData)
    const m = /\.([A-Za-z0-9]+)$/.exec(name)
    return m ? m[1].toLowerCase() : ""
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
    const e = ext(fileData)

    // MIME-based detection
    const byMime = mime.includes("text/html") || mime.includes("application/html") || mime.includes("application/xhtml")

    // Extension-based fallback when MIME is missing/wrong
    const byExt = e === "html" || e === "htm" || e === "xhtml"

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
