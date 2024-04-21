import { computed, ref } from "vue"
import { useRoute } from "vue-router"
import { usePlatformConfig } from "../../store/platformConfig"
import { checkIsAllowedToEdit } from "../userPermissions"

export function useDocumentActionButtons() {
  const route = useRoute()

  const platformConfigStore = usePlatformConfig()

  const isCertificateMode = computed(() => {
    return route.query.filetype === "certificate"
  })

  const showNewDocumentButton = ref(false)
  const showUploadButton = ref(false)
  const showNewFolderButton = ref(false)
  const showNewDrawingButton = ref(false)
  const showRecordAudioButton = ref(false)
  const showNewCloudFileButton = ref(false)
  const showSlideshowButton = ref(false)
  const showUsageButton = ref(false)
  const showDownloadAllButton = ref(false)

  const showNewCertificateButton = ref(false)
  const showUploadCertificateButton = ref(false)

  checkIsAllowedToEdit(false, true).then((isAllowedToEdit) => {
    if (isAllowedToEdit) {
      if (!isCertificateMode.value) {
        showNewDocumentButton.value = true
        showRecordAudioButton.value = "true" === platformConfigStore.getSetting("course.enable_record_audio")
        showUploadButton.value = true
        showNewFolderButton.value = true
        showNewCloudFileButton.value = true // enable_add_file_link ?
        showSlideshowButton.value = true // disable_slideshow_documents ?
        showUsageButton.value = true
      } else {
        showNewCertificateButton.value = true
        showUploadCertificateButton.value = true
      }
    }

    if (
      !isCertificateMode.value &&
      ("true" === platformConfigStore.getSetting("document.students_download_folders") || isAllowedToEdit.value)
    ) {
      showDownloadAllButton.value = true
    }
  })

  return {
    showNewDocumentButton,
    showUploadButton,
    showNewFolderButton,
    showNewDrawingButton,
    showRecordAudioButton,
    showNewCloudFileButton,
    showSlideshowButton,
    showUsageButton,
    showDownloadAllButton,
    showNewCertificateButton,
    showUploadCertificateButton,
  }
}
