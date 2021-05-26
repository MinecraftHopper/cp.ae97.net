<template>
  <v-app>
    <v-navigation-drawer app permanent expand-on-hover>
      <v-list-item>
        <v-list-item-icon>
          <v-icon>mdi-bluetooth</v-icon>
        </v-list-item-icon>
        <v-list-item-content>
          <v-list-item-title class="title">
            Hopper Panel
          </v-list-item-title>
        </v-list-item-content>
      </v-list-item>

      <v-divider></v-divider>


      <v-list dense nav mini>
        <router-link to="/factoids" tag="div">
          <v-list-item link>
            <v-list-item-icon>
              <v-icon>mdi-account-question</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <span>Factoids</span>
            </v-list-item-content>
          </v-list-item>
        </router-link>

        <router-link to="/hjt" tag="div">
          <v-list-item link>
            <v-list-item-icon>
              <v-icon>mdi-bug</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <span>HJT</span>
            </v-list-item-content>
          </v-list-item>
        </router-link>

        <router-link to="/users" tag="div" v-if="canEditUsers">
          <v-list-item link>
            <v-list-item-icon>
              <v-icon>mdi-account-group</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <span>Users</span>
            </v-list-item-content>
          </v-list-item>
        </router-link>

        <v-divider></v-divider>

        <v-list-item link v-if="loggedIn" v-on:click="logout">
          <v-list-item-icon>
            <v-icon>mdi-account-arrow-right</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <span>Logout</span>
          </v-list-item-content>
        </v-list-item>
        <v-list-item link v-else v-on:click="login">
          <v-list-item-icon>
            <v-icon>mdi-account-arrow-right</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <span>Login</span>
          </v-list-item-content>
        </v-list-item>
      </v-list>

      <v-list-item slot="append">
        <v-list-item-icon v-on:click="toggleTheme">
          <v-icon>mdi-lightbulb</v-icon>
        </v-list-item-icon>
      </v-list-item>
    </v-navigation-drawer>

    <v-main>
      <v-container fluid>
        <router-view></router-view>
      </v-container>
    </v-main>

    <v-footer app>
      <span><small>Copyright 2021&#169; MinecraftHopper</small></span>
    </v-footer>
  </v-app>
</template>

<style>
#nav a {
  font-weight: bold;
  color: #2c3e50;
}
</style>

<script>
import axios from "axios";

export default {
  data() {
    return {
      canEditUsers: false,
      loggedIn: false
    }
  },
  beforeCreate() {
    const t = localStorage.getItem("dark-theme");
    //if the storage doesn't indicate one, and the system is dark, use dark
    if ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches && !t) || t === 'dark') {
      this.$vuetify.theme.dark = true;
    }
  },
  mounted() {
    if (this.$cookies.get("perms")?.split("+").includes("user.manage")) {
      this.canEditUsers = true;
    }
    if (this.$cookies.get("panelsession")) {
      this.loggedIn = true
    }
  },
  methods: {
    toggleTheme: function () {
      localStorage.setItem("dark-theme", !this.$vuetify.theme.dark ? "dark" : "light");
      this.$vuetify.theme.dark = !this.$vuetify.theme.dark;
    },
    logout: function () {
      axios.get('/logout').then(() => {
        this.loggedIn = false
        this.$cookies.remove("perms")
        this.$cookies.remove("panelsession")
      })
    },
    login: function () {
      window.location.href = "/login";
    }
  }
}

</script>
