<template>
  <v-card class="mx-auto">
    <v-text-field
        v-model="search"
        label="Search"
        class="mx-4"
    ></v-text-field>

    <v-list three-line>
      <v-list-group v-for="factoid in filteredFactoids" :key="factoid.name" no-action active-class="">
        <template v-slot:activator>
          <v-list-item-content>
            <v-list-item-title v-text="factoid.name"></v-list-item-title>
            <v-list-item-subtitle class="wrap-text" v-text="factoid.content"></v-list-item-subtitle>
          </v-list-item-content>
        </template>

        <v-list-item v-if="canEdit">
          <v-list-item-action>
            <v-list-item-action-text><v-btn>Edit</v-btn></v-list-item-action-text>
          </v-list-item-action>
        </v-list-item>
      </v-list-group>
    </v-list>
  </v-card>
</template>

<style>
.wrap-text {
  -webkit-line-clamp: unset !important;
}
</style>

<script>
import axios from 'axios'
import debounce from 'lodash/debounce'

export default {
  name: 'Factoids',
  components: {},
  data () {
    return {
      search: '',
      factoids: null,
      filteredFactoids: null,
      filtering: false,
      canEdit: false
    }
  },
  mounted () {
    axios.get('/api/factoid').then(response => this.factoids = response.data)
    this.canEdit = true;
  },
  methods: {
    filterBasedOnSearch: debounce(function() {
      this.filtering = true;
        if (!this.search) {
          this.filteredFactoids = this.factoids;
          return
        }
        this.filteredFactoids = this.factoids.filter(function (factoid) {
          return factoid.name.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
              || factoid.content.toLocaleLowerCase().includes(this.search.toLocaleLowerCase());
        }, this)
    }, 500)
  },
  watch: {
    search: function() {
      this.filtering = true;
      this.filterBasedOnSearch();
    },
    factoids: function() {
      this.filteredFactoids = this.factoids;
    }
  }
}
</script>
