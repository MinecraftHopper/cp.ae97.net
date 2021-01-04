// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
// Components
import './components'
// Plugins
// Application imports
import App from './App'
import router from '@/router'
import '@/styles/core.css'

if ('serviceWorker' in navigator) {
  if (process.env.NODE_ENV === 'production') {
    navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
  } else {
    navigator.serviceWorker.register('/service-worker-dev.js', { scope: '/' })
  }
}

//Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
  router,
  render: h => h(App)
}).$mount('#app')