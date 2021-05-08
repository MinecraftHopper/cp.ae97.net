<template>
  <v-card class="mx-auto">
    <v-text-field
        v-model="discordId"
        label="Search by Discord ID"
        class="mx-4"
        @input="searchDiscord"
    ></v-text-field>

    <v-card v-if="discordId.length === 18" :loading="searching">
      <template slot="progress">
        <v-progress-linear
            color="deep-purple"
            height="10"
            indeterminate
        ></v-progress-linear>
      </template>

      <v-card-title v-if="hasRecord">
        <v-avatar size="56">
          <img
              alt="user"
              :src="'https://cdn.discordapp.com/avatars/' + discordUser.user.id + '/' + discordUser.user.avatar + '.png?size=64'"
          >
        </v-avatar>
        <p class="ml-3" v-text="discordUser.user.username + '#' + discordUser.user.discriminator + ' (' + discordUser.user.id + ')'"></p>
      </v-card-title>
      <v-card-text v-if="searching">
        <v-icon>mdi-spinner</v-icon>
      </v-card-text>
      <v-card-text v-else-if="error !== ''">
        <span v-text="error"></span>
      </v-card-text>
      <v-card-text v-else>
        <div v-for="(desc, flag) in allowedFlags" v-bind:key="flag">
          <v-checkbox :label="desc" :value="flag" v-model="discordUser.perms">
          </v-checkbox>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-btn v-on:click="save">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-card>
</template>

<script>
import axios from "axios";

export default {
  name: "Users",
  data() {
    return {
      discordId: '',
      searching: false,
      discordUser: {
        user: {},
        perms: []
      },
      allowedFlags: [],
      error: '',
      hasRecord: false,
    }
  },
  mounted() {
    axios.get('/api/flags/').then((e) => {
      this.allowedFlags = e.data
    })
  },
  methods: {
    search: function() {
      this.searching = true
      this.hasRecord = false
      this.discordUser = {
        user: {},
        perms: []
      }
    },
    searchDiscord: function() {
      if (this.discordId.length === 18) {
        axios.get('/api/flags/' + this.discordId).then((e) => {
          this.searching = false
          this.hasRecord = true
          this.discordUser = e.data
        }).catch((e) => {
          this.error = e.data.message
          this.searching = false
        });
      }
    },
    save: function() {
      this.searching = true
      this.hasRecord = true
      axios.put('/api/flags/' + this.discordUser.user.id, this.discordUser.perms).then(() => {
        this.searching = false
        this.hasRecord = true
      });
    }
  }
}
</script>

<style scoped>

</style>