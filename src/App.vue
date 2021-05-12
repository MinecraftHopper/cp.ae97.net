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
        <v-list-item link>
          <v-list-item-icon>
            <v-icon>mdi-account-question</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <router-link to="/factoids">Factoids</router-link>
          </v-list-item-content>
        </v-list-item>

        <v-list-item link>
          <v-list-item-icon>
            <v-icon>mdi-bug</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <router-link to="/hjt">HJT</router-link>
          </v-list-item-content>
        </v-list-item>

        <v-list-item link v-if="canEditUsers">
          <v-list-item-icon>
            <v-icon>mdi-account-group</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <router-link to="/users">Users</router-link>
          </v-list-item-content>
        </v-list-item>

        <v-divider></v-divider>

        <v-list-item link v-if="loggedIn">
          <v-list-item-icon>
            <v-icon>mdi-account-arrow-right</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <a href="#" v-on:click="logout">Logout</a>
          </v-list-item-content>
        </v-list-item>
        <v-list-item link v-else>
          <v-list-item-icon>
            <v-icon>mdi-account-arrow-right</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <a href="/login">Login</a>
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
  data () {
    return {
      canEditUsers: false,
      loggedIn: false
    }
  },
  beforeCreate() {
    const t = localStorage.getItem("dark-theme");
    if (t === "dark") {
      this.$vuetify.theme.dark = true;
    }
  },
  mounted() {
    if (this.$cookies.get("perms").split("+").includes("user.manage")) {
      this.canEditUsers = true;
    }
    if (this.$cookies.get("perms")) {
      this.loggedIn = true
    }
  },
  methods: {
    toggleTheme: function() {
      localStorage.setItem("dark-theme", !this.$vuetify.theme.dark ? "dark": "light");
      this.$vuetify.theme.dark = !this.$vuetify.theme.dark;
    },
    logout: function() {
      axios.post('/auth/logout').then(() => {
        this.loggedIn = false
        this.$cookies.remove("perms")
        this.$cookies.remove("panelsession")
      })
    }
  }
}

</script>
