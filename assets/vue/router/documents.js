export default {
  path: '/resources/document/:node/',
  meta: { requiresAuth: true },
  name: 'documents',
  component: () => import('../components/documents/Layout'),
  redirect: { name: 'DocumentsList' },
  children: [
    {
      name: 'DocumentsList',
      path: '',
      component: () => import('../views/documents/List')
    },
    {
      name: 'DocumentsCreate',
      path: 'new',
      component: () => import('../views/documents/Create')
    },
    {
      name: 'DocumentsCreateFile',
      path: 'new_file',
      component: () => import('../views/documents/CreateFile')
    },
    {
      name: 'DocumentsUploadFile',
      path: 'upload',
      component: () => import('../views/documents/Upload')
    },
    {
      name: 'DocumentsUpdate',
      //path: ':id/edit',
      path: 'edit',
      component: () => import('../views/documents/Update')
    },
    {
      name: 'DocumentsUpdateFile',
      //path: ':id/edit',
      path: 'edit_file',
      component: () => import('../views/documents/UpdateFile')
    },
    {
      name: 'DocumentsShow',
      //path: ':id',
      path: 'show',
      component: () => import('../views/documents/Show')
    }
  ]
};
