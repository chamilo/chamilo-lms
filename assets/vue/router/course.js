export default {
  path: '/resources/courses',
  meta: { requiresAuth: true },
  name: 'courses',
  component: () => import('../components/course/Layout'),
  redirect: { name: 'CourseList' },
  children: [
    {
      name: 'CourseList',
      path: '',
      component: () => import('../views/course/List')
    },
    {
      name: 'CourseCreate',
      path: 'new',
      component: () => import('../views/course/Create')
    },
    {
      name: 'CourseUpdate',
      path: ':id/edit',
      component: () => import('../views/course/Update')
    },
    {
      name: 'CourseShow',
      path: ':id',
      component: () => import('../views/course/Show')
    }
  ]
};
