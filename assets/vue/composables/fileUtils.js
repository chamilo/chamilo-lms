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
    const mimeType = fileData.resourceNode.firstResourceFile.mimeType
    return mimeType.split("/")[1].toLowerCase() === "html"
  }

  const isPreviewable = (fileData) => {
    return (
      isImage(fileData) ||
      isVideo(fileData) ||
      isAudio(fileData)
    )
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
