export default {
  path: '/resources/course_categories',
  meta: { requiresAuth: true },
  name: 'course_categories',
  component: () => import('../components/coursecategory/Layout'),
  redirect: { name: 'CourseCategoryList' },
  children: [
    {
      name: 'CourseCategoryList',
      path: '',
      component: () => import('../views/coursecategory/List')
    },
    {
      name: 'CourseCategoryCreate',
      path: 'new',
      component: () => import('../views/coursecategory/Create')
    },
    {
      name: 'CourseCategoryUpdate',
      path: ':id/edit',
      component: () => import('../views/coursecategory/Update')
    },
    {
      name: 'CourseCategoryShow',
      path: ':id',
      component: () => import('../views/coursecategory/Show')
    }
  ]
};
