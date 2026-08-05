// From
// https://ckeditor.com/docs/ckeditor5/latest/framework/guides/deep-dive/upload-adapter.html

import documentsService from "../../services/documents"

export default class MyUploadAdapter {
  constructor(loader) {
    // The file loader instance to use during the upload.
    this.loader = loader
    // Allows aborting the in-flight request from abort().
    this.controller = new AbortController()
  }

  // Starts the upload process.
  upload() {
    return this.loader.file.then((file) => this._sendRequest(file))
  }

  // Aborts the upload process.
  abort() {
    this.controller.abort()
  }

  // Prepares the data and sends the request through the documents service.
  async _sendRequest(file) {
    const genericErrorText = `Couldn't upload file: ${file.name}.`
    const loader = this.loader

    // Prepare the form data.
    const data = new FormData()

    // Chamilo
    data.append("filetype", "file")
    data.append("parentResourceNodeId", "4")
    data.append("uploadFile", file)

    try {
      const response = await documentsService.uploadDocumentFile(data, {
        signal: this.controller.signal,
        // The file loader has the #uploadTotal and #uploaded properties which are
        // used e.g. to display the upload progress bar in the editor user interface.
        onUploadProgress: (evt) => {
          if (evt.lengthComputable) {
            loader.uploadTotal = evt.total
            loader.uploaded = evt.loaded
          }
        },
      })

      if (!response || response.error) {
        throw new Error(response && response.error ? response.error.message : genericErrorText)
      }

      // Resolve the upload promise with an object containing at least the "default"
      // URL, pointing to the image on the server. This URL is used to display the
      // image in the content.
      return { default: response.contentUrl }
    } catch (error) {
      const apiMessage = error?.response?.data?.error?.message
      throw new Error(apiMessage || error?.message || genericErrorText, { cause: error })
    }
  }
}
