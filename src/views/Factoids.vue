<template>
  <v-card class="mx-auto">
    <v-text-field v-if="!editing"
        v-model="search"
        label="Search"
        class="mx-4"
        @input="filterBasedOnSearch"
    ></v-text-field>

    <v-list three-line v-if="!editing">
      <v-list-item-content v-for="factoid in filteredFactoids" :key="factoid.name">
        <v-card>
          <v-card-title class="font-weight-bold" v-text="factoid.name"></v-card-title>
          <v-card-text><span v-html="markdown(factoid.content)"/></v-card-text>
          <v-card-actions v-if="canEdit">
            <v-btn v-on:click="startEdit(factoid.name)">Edit</v-btn>
          </v-card-actions>
        </v-card>
      </v-list-item-content>
    </v-list>

    <!--Editor -->
    <v-card v-if="editing">
      <v-card-title class="font-weight-bold" v-text="editingKey"></v-card-title>
      <v-card-text>
        <div id="editor">
          <textarea :value="editorText" @input="updatePreview"></textarea>
        </div>
      </v-card-text>
    </v-card>
    <v-card v-if="editing">
      <v-card-title class="font-weight-bold">Preview</v-card-title>
      <v-card-text>
        <div v-html="compiledMarkdown"></div>
      </v-card-text>
      <v-card-actions>
        <v-btn v-on:click="saveEdit">Save</v-btn>
        <v-btn v-on:click="cancelEdit">Cancel</v-btn>
        <v-btn v-on:click="deleteItem">Delete</v-btn>
      </v-card-actions>
    </v-card>
  </v-card>
</template>

<style>
#editor {
  margin: 0;
  height: 100%;
  font-family: "Helvetica Neue", Arial, sans-serif;
  color: #333;
}

textarea,
#editor div {
  display: inline-block;
  width: 100%;
  height: 100%;
  vertical-align: top;
  box-sizing: border-box;
  padding: 0 20px;
}

textarea {
  border: none;
  border-right: 1px solid #ccc;
  resize: none;
  outline: none;
  background-color: #f6f6f6;
  font-size: 14px;
  font-family: "Monaco", courier, monospace;
  padding: 20px;
}

code {
  color: #f66;
}

</style>

<script>
import axios from 'axios'
import debounce from 'lodash/debounce'
import marked from 'marked'
import markdown from '@/utils/markdown'

export default {
  name: 'Factoids',
  components: {},
  data () {
    return {
      search: '',
      filteredFactoids: [],
      filtering: false,
      canEdit: false,
      factoids: null,
      editing: false,
      editorText: '',
      editingKey: '',
    }
  },
  mounted () {
    this.getFactoids();
    if (this.$cookies.get("perms").split(" ").includes("factoid.manage")) {
      this.canEdit = true;
    }
  },
  methods: {
    getFactoids: function() {
      axios.get('/api/factoid').then(response => {
        this.factoids = response.data;
        this.filteredFactoids = [];
        for (const rec of this.factoids) {
          this.filteredFactoids.push(Object.assign({}, rec));
        }
      })
    },
    filterBasedOnSearch: debounce(function () {
      this.filtering = true;
      if (!this.search) {
        this.filteredFactoids = this.factoids;
        return;
      }
      this.filteredFactoids = this.factoids.filter(function (factoid) {
        return factoid.name.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
            || factoid.content.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
      }, this);
    }, 500),
    updatePreview: function(e) {
      this.editorText = e.target.value;
    },
    startEdit: function(key) {
      this.editing = true;
      this.editingKey = key;
      for (const factoid of this.factoids) {
        if (factoid.name === key) {
          this.editorText = factoid.content;
          break;
        }
      }
    },
    cancelEdit: function() {
      this.editorText = '';
      this.editingKey = '';
      this.editing = false;
    },
    saveEdit: function() {
      axios.put('/api/factoid/' + this.editingKey, this.editorText).then(() => {
        this.getFactoids();
        this.cancelEdit();
        this.filterBasedOnSearch();
      });
    },
    deleteItem: function() {
      if (confirm("Are you sure you want to delete this factoid?")) {
        axios.delete('/api/factoid/' + this.editingKey).then(() => {
          this.getFactoids();
          this.cancelEdit();
          this.filterBasedOnSearch();
        });
      }
    },
    markdown
  },
  computed: {
    compiledMarkdown: function() {
      return marked(this.editorText, { sanitizer: markdown });
    }
  },
}
</script>
