export default {
  path: '/resources/document/:node/',
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: 'documents',
  component: () => import('../components/layout/SimpleRouterViewLayout.vue'),
  redirect: { name: 'DocumentsList' },
  children: [
    {
      name: 'DocumentsList',
      path: '',
      component: () => import('../views/documents/DocumentsList.vue')
    },
    {
      name: 'DocumentsCreate',
      path: 'new',
      component: () => import('../views/documents/Create.vue')
    },
    {
      name: 'DocumentsCreateFile',
      path: 'create',
      component: () => import('../views/documents/CreateFile.vue')
    },
    {
      name: 'DocumentsUploadFile',
      path: 'upload',
      component: () => import('../views/documents/DocumentsUpload.vue')
    },
    {
      name: 'DocumentsUpdate',
      //path: ':id/edit',
      path: 'edit',
      component: () => import('../views/documents/Update.vue')
    },
    {
      name: 'DocumentsUpdateFile',
      //path: ':id/edit',
      path: 'edit_file',
      component: () => import('../views/documents/UpdateFile.vue')
    },
    {
      name: 'DocumentsShow',
      path: 'show',
      component: () => import('../views/documents/DocumentShow.vue')
    },
    {
      name: 'DocumentForHtmlEditor',
      path: 'manager',
      component: () => import('../views/documents/DocumentForHtmlEditor.vue'),
      meta: {
        layout: 'Empty',
        showBreadcrumb: false
      }
    },
  ]
};
