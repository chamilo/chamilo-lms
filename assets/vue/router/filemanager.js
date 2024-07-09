export default {
  path: '/resources/filemanager',
  meta: { requiresAuth: true },
  component: () => import('../components/filemanager/Layout.vue'),
  children: [
    {
      path: 'personal_list/:node?',
      name: 'FileManagerList',
      component: () => import('../views/filemanager/List.vue'),
      meta: { emptyLayout: true },
    },
    {
      name: 'FileManagerUploadFile',
      path: 'upload',
      component: () => import('../views/filemanager/Upload.vue'),
      meta: { emptyLayout: true },
    },
    {
      name: 'CourseDocumentsUploadFile',
      path: '/course-upload',
      meta: { emptyLayout: true },
      component: () => import('../views/documents/DocumentsUpload.vue')
    },
  ],
};
