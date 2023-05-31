

export function useFileUtils() {

  const isImage = (fileData) => {
    return isFile(fileData) && fileData.resourceNode.resourceFile.image
  }

  const isVideo = (fileData) => {
    return isFile(fileData) && fileData.resourceNode.resourceFile.video
  }

  const isFile = (fileData) => {
    return fileData.resourceNode && fileData.resourceNode.resourceFile
  }

  return {
    isFile,
    isImage,
    isVideo,
  }
}
