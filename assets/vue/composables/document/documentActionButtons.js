import { computed } from "vue"
import { useRoute } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"

export function useDocumentActionButtons() {
  const route = useRoute()
  const securityStore = useSecurityStore()
  const platformConfigStore = usePlatformConfig()

  const inStudentView = computed(() => platformConfigStore.isStudentViewActive)
  const isTeacherUI = computed(
    () =>
      (securityStore.isCurrentTeacher || securityStore.isCourseAdmin || securityStore.isAdmin) && !inStudentView.value,
  )

  const isCertificateMode = computed(() => route.query.filetype === "certificate")

  const showNewDocumentButton = computed(() => isTeacherUI.value && !isCertificateMode.value)
  const showUploadButton = computed(() => isTeacherUI.value && !isCertificateMode.value)
  const showNewFolderButton = computed(() => isTeacherUI.value && !isCertificateMode.value)
  const showNewDrawingButton = computed(() => isTeacherUI.value && !isCertificateMode.value)
  const showRecordAudioButton = computed(() => isTeacherUI.value && !isCertificateMode.value)
  const showNewCloudFileButton = computed(() => isTeacherUI.value && !isCertificateMode.value)

  const showSlideshowButton = computed(() => true)

  const showUsageButton = computed(() => isTeacherUI.value)

  const showDownloadAllButton = computed(() => securityStore.isAuthenticated)

  const showNewCertificateButton = computed(() => isTeacherUI.value && isCertificateMode.value)
  const showUploadCertificateButton = computed(() => isTeacherUI.value && isCertificateMode.value)

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
