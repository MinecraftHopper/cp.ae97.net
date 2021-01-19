<template>
  <v-card>
    <v-text-field
        v-model="search"
        label="Search"
        class="mx-4"
    ></v-text-field>

    <v-list-item two-line v-for="factoid in filteredFactoids" :key="factoid.name">
      <v-list-item-content>
        <v-list-item-title v-text="factoid.name"></v-list-item-title>
        <v-list-item-subtitle v-text="factoid.content"></v-list-item-subtitle>
      </v-list-item-content>
    </v-list-item>
  </v-card>
</template>

<script>
import axios from 'axios'
import debounce from 'lodash/debounce'
//var curryN = require('lodash/debounce');

export default {
  name: 'Factoids',
  components: {},
  data () {
    return {
      search: '',
      factoids: null,
      filteredFactoids: null,
      filtering: false
    }
  },
  mounted () {
    axios.get('/api/factoid').then(response => this.factoids = response.data)
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
