import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

const routes = [
  {
    path: '/',
    name: 'Factoids',
    component: () => import(/* webpackChunkName: "home" */ '../views/Factoids.vue')
  },
  {
    path: '/factoids',
    name: 'Factoids',
    component: () => import(/* webpackChunkName: "home" */ '../views/Factoids.vue')
  },
  {
    path: '/about',
    name: 'About',
    component: () => import(/* webpackChunkName: "about" */ '../views/About.vue')
  }
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
})

export default router
