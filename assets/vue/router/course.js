export default {
  path: '/resources/courses',
  meta: { requiresAuth: true },
  name: 'courses',
  component: () => import('../components/course/Layout.vue'),
  redirect: { name: 'CourseList' },
  children: [
    {
      name: 'CourseList',
      path: '',
      component: () => import('../views/course/List.vue')
    },
    {
      name: 'CourseCreate',
      path: 'new',
      component: () => import('../views/course/Create.vue')
    },
    {
      name: 'CourseUpdate',
      path: ':id/edit',
      component: () => import('../views/course/Update.vue')
    },
    {
      name: 'CourseShow',
      path: ':id',
      component: () => import('../views/course/Show.vue')
    }
  ]
};
