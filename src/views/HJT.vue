<template>
  <v-card class="mx-auto">
    <v-text-field v-if="!editingHjt"
                  v-model="search"
                  label="Search"
                  class="mx-4"
                  @input="filterBasedOnSearch"
    ></v-text-field>

    <v-btn v-if="canEdit" v-on:click="startEdit()">Create</v-btn>

    <v-list three-line v-if="!editingHjt">
      <v-list-item-content v-for="hjt in filteredHjts" :key="hjt.id">
        <v-card>
          <v-card-title class="font-weight-bold"
                        v-text="hjt.name + (hjt.match_criteria === hjt.name ? '' : ' (' + hjt.match_criteria + ')')"></v-card-title>
          <v-card-text>
            <span v-html="'Severity: ' + hjt.severity_description"/>
            <br/>
            <span v-html="hjt.description"/>
          </v-card-text>
          <v-card-actions v-if="canEdit">
            <v-btn v-on:click="startEdit(hjt.id)">Edit</v-btn>
          </v-card-actions>
        </v-card>
      </v-list-item-content>
    </v-list>

    <!--Editor -->
    <v-card v-if="editingHjt">
      <v-card-title class="font-weight-bold" v-text="editingHjt.name"></v-card-title>
      <v-card-text>
        <v-text-field label="Name" v-model="editingHjt.name"></v-text-field>
        <v-text-field label="Match Criteria" v-model="editingHjt.match_criteria"></v-text-field>
        <v-text-field label="Category" v-model="editingHjt.category"></v-text-field>
        <v-select label="Severity" v-model="editingHjt.severity" :items="severityOptions" item-text="description"
                  item-value="id">
        </v-select>
        <div id="editor">
          <textarea :value="editingHjt.description" @input="updatePreview"></textarea>
        </div>
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

export default {
  name: 'HJT',
  components: {},
  data() {
    return {
      search: '',
      filteredHjts: [],
      filtering: false,
      canEdit: false,
      hjts: null,
      editingHjt: null,
      severityOptions: [
        {
          id: 0,
          description: "Info"
        },
        {
          id: 1,
          description: "Low"
        },
        {
          id: 2,
          description: "Medium"
        },
        {
          id: 3,
          description: "High"
        }
      ]
    }
  },
  mounted() {
    this.getHJTs();
    if (this.$cookies.get("perms").split("+").includes("hjt.manage")) {
      this.canEdit = true;
    }
  },
  methods: {
    getHJTs: function () {
      axios.get('/api/hjt').then(response => {
        this.hjts = response.data;
        this.filteredHjts = [];
        for (const rec of this.hjts) {
          this.filteredHjts.push(Object.assign({}, rec));
        }
      })
    },
    filterBasedOnSearch: debounce(function () {
      if (!this.search) {
        this.filteredHjts = this.hjts;
        return;
      }
      this.filteredHjts = this.hjts.filter(function (hjt) {
        return hjt.name.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
            || hjt.description.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
            || hjt.category.toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
      }, this);
    }, 500),
    updatePreview: function (e) {
      this.editingHjt.description = e.target.value;
    },
    startEdit: function (key) {
      this.editing = true;
      for (const hjt of this.hjts) {
        if (hjt.id === key) {
          this.editingHjt = hjt;
          return;
        }
      }
      if (!this.editingHjt) {
        this.editingHjt = {};
      }
    },
    cancelEdit: function () {
      this.editingHjt = null;
    },
    saveEdit: function () {
      //fix description since it's set by the pull....
      this.editingHjt.severity_description = "";
      if (this.editingHjt.id) {
        axios.put('/api/hjt/' + this.editingHjt.id, this.editingHjt).then(this.refresh);
      } else {
        axios.post('/api/hjt', this.editingHjt).then(this.refresh);
      }
    },
    deleteItem: function () {
      if (confirm("Are you sure you want to delete this HJT?")) {
        axios.delete('/api/hjt/' + this.editingHjt.id).then(() => {
          this.getHJTs();
          this.cancelEdit();
          this.filterBasedOnSearch();
        });
      }
    },
    refresh: function () {
      this.getHJTs();
      this.cancelEdit();
      this.filterBasedOnSearch();
    }
  }
}
</script>
