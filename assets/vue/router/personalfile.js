export default {
  path: '/resources/personal_files',
  meta: { requiresAuth: true },
  name: 'personal_files',
  component: () => import('../views/personalfile/Home.vue'),
  children: [
    {
      name: 'personal_files',
      path: ':node/',
      component: () => import('../components/personalfile/Layout.vue'),
      redirect: { name: 'PersonalFileList' },
      children: [
        {
          name: 'PersonalFileList',
          path: '',
          component: () => import('../views/personalfile/List.vue')
        },
        {
          name: 'PersonalFileCreate',
          path: 'new',
          component: () => import('../views/personalfile/Create.vue')
        },
        {
          name: 'PersonalFileUploadFile',
          path: 'upload',
          component: () => import('../views/personalfile/Upload.vue')
        },
        {
          name: 'PersonalFileUpdate',
          //path: ':id/edit',
          path: 'edit_file',
          component: () => import('../views/personalfile/Update.vue')
        },
        {
          name: 'PersonalFileShow',
          path: 'show',
          component: () => import('../views/personalfile/Show.vue')
        }
      ]
    },
  ]
};
