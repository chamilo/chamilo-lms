export default {
  path: '/resources/documents/:node/',
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
      path: 'new',
      component: () => import('../views/documents/CreateFile')
    },
    {
      name: 'DocumentsUpdate',
      path: ':id/edit',
      component: () => import('../views/documents/Update')
    },
    {
      name: 'DocumentsShow',
      path: ':id',
      component: () => import('../views/documents/Show')
    }
  ]
};
