export function useFileUtils() {
  const isFile = (fileData) => {
    return fileData.resourceNode && fileData.resourceNode.firstResourceFile
  }

  const isImage = (fileData) => {
    return isFile(fileData) && fileData.resourceNode.firstResourceFile.image
  }

  const isVideo = (fileData) => {
    return isFile(fileData) && fileData.resourceNode.firstResourceFile.video
  }

  const isAudio = (fileData) => {
    const mimeType = fileData.resourceNode.firstResourceFile.mimeType
    return isFile(fileData) && mimeType.split("/")[0].toLowerCase() === "audio"
  }

  const isHtml = (fileData) => {
    if (!isFile(fileData)) {
      return false
    }
    const mimeType = fileData.resourceNode.firstResourceFile.mimeType || ""
    const [type, sub] = mimeType.split("/")
    return (type?.toLowerCase() === "text" && sub?.toLowerCase() === "html") || sub?.toLowerCase() === "html"
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
