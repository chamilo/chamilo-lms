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
    const isAudio = mimeType.split("/")[0].toLowerCase() === "audio"
    return isFile(fileData) && isAudio
  }

  const isHtml = (fileData) => {
    if (!isFile(fileData)) {
      return false
    }
    const mimeType = fileData.resourceNode.firstResourceFile.mimeType
    return mimeType.split("/")[1].toLowerCase() === "html"
  }

  const isPreviewable = (fileData) => {
    const mimeType = fileData.resourceNode.firstResourceFile.mimeType.toLowerCase()
    return isImage(fileData) || isVideo(fileData) || isAudio(fileData) || isHtml(fileData) || mimeType === "application/pdf"
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
