export default {
  path: '/resources/document/:node/',
  meta: { requiresAuth: true },
  name: 'documents',
  component: () => import('../components/documents/Layout.vue'),
  redirect: { name: 'DocumentsList' },
  children: [
    {
      name: 'DocumentsList',
      path: '',
      component: () => import('../views/documents/List.vue')
    },
    {
      name: 'DocumentManager',
      path: 'manager',
      component: () => import('../views/documents/DocumentManager.vue'),
      meta: {
        layout: 'Empty'
      }
    },
    {
      name: 'DocumentsCreate',
      path: 'new',
      component: () => import('../views/documents/Create.vue')
    },
    {
      name: 'DocumentsCreateFile',
      path: 'new_file',
      component: () => import('../views/documents/CreateFile.vue')
    },
    {
      name: 'DocumentsUploadFile',
      path: 'upload',
      component: () => import('../views/documents/Upload.vue')
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
      component: () => import('../views/documents/Show.vue')
    }
  ]
};
