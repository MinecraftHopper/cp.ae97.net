<template>
  <v-card class="mx-auto">
    <v-text-field
        v-model="search"
        label="Search"
        class="mx-4"
    ></v-text-field>

    <v-list three-line>
      <v-list-item-content v-for="factoid in filteredFactoids" :key="factoid.name">
        <v-card>
          <v-card-title class="font-weight-bold" v-text="factoid.name"></v-card-title>
          <v-card-text v-text="factoid.content"></v-card-text>
        </v-card>
      </v-list-item-content>
    </v-list>
  </v-card>
</template>

<style>
</style>

<script>
import axios from 'axios'
//import debounce from 'lodash/debounce'

export default {
  name: 'Factoids',
  components: {},
  data () {
    return {
      search: '',
      filteredFactoids: [],
      filtering: false,
      canEdit: false,
      factoids: null
    }
  },
  mounted () {
    axios.get('/api/factoid').then(response => {
      this.factoids = response.data
      for (const rec of this.factoids) {
        this.filteredFactoids.push(Object.assign({}, rec))
      }
    })

    this.canEdit = true
  },
  methods: {
    filterBasedOnSearch: /*debounce(*/function () {
      this.filtering = true
      if (!this.search) {
        this.filteredFactoids = this.factoids
        return
      }
      this.filteredFactoids = this.factoids.filter(function (factoid) {
        return factoid.name.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
            || factoid.content.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
      }, this)
    }/*, 500)*/
  },
  watch: {
    search: function () {
      this.filtering = true
      this.filterBasedOnSearch()
    }
  }
}
</script>
