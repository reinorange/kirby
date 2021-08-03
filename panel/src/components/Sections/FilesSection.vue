<template>
  <section class="k-files-section">
    <header class="k-section-header">
      <k-headline>
        {{ headline }} <abbr v-if="min" :title="$t('section.required')">*</abbr>
      </k-headline>
      <k-button-group v-if="add">
        <k-button icon="upload" @click="upload">
          {{ $t("add") }}
        </k-button>
      </k-button-group>
    </header>

    <k-async ref="async" :endpoint="$view.path + '/sections/' + name">
      <template #error="{ error, reload }">
        {{ error }}
      </template>
      <template #default="{ isLoading, response, reload }">
        <k-dropzone :disabled="add === false" @drop="drop">
          <k-collection
            v-if="!isLoading && response.items.length"
            :help="help"
            :items="items(response.items)"
            :layout="layout"
            :pagination="response.pagination"
            :sortable="!isProcessing && sortable"
            :size="size"
            @sort="sort"
            @paginate="paginate"
            @action="action"
          />
          <template v-else>
            <k-empty
              :layout="layout"
              :data-invalid="isInvalid"
              icon="image"
              @click="upload"
            >
              {{ empty || $t('files.empty') }}
            </k-empty>
            <footer class="k-collection-footer">
              <!-- eslint-disable vue/no-v-html -->
              <k-text
                v-if="help"
                theme="help"
                class="k-collection-help"
                v-html="help"
              />
              <!-- eslint-enable vue/no-v-html -->
            </footer>
          </template>

          <k-upload ref="upload" @success="$reload" @error="$reload" />
        </k-dropzone>
      </template>
    </k-async>

  </section>
</template>

<script>
import CollectionSectionMixin from "@/mixins/section/collection.js";

export default {
  mixins: [CollectionSectionMixin],
  computed: {
    add() {
      if (this.$permissions.files.create && this.upload !== false) {
        return this.upload;
      } else {
        return false;
      }
    },
  },
  methods: {
    action(action, file) {

      switch (action) {
        case "replace":
          this.$refs.upload.open({
            url: this.$urls.api + "/" + this.$api.files.url(file.parent, file.filename),
            accept: "." + file.extension + "," + file.mime,
            multiple: false
          });
          break;
      }

    },
    drop(files) {
      if (this.add === false) {
        return false;
      }

      this.$refs.upload.drop(files, {
        ...this.add,
        url: this.$urls.api + "/" + this.add.api
      });
    },
    items(data) {
      return data.map(file => {
        file.sortable = this.sortable;
        file.column   = this.column;
        file.options  = this.$dropdown(this.$api.files.url(file.parent, file.filename), {
          query: {
            view: "list",
            update: this.sortable,
            delete: data.length > this.min
          }
        });

        // add data-attributes info for item
        file.data = {
          "data-id": file.id,
          "data-template": file.template
        };

        return file;
      });
    },
    replace(file) {
      this.$refs.upload.open({
        url: this.$urls.api + "/" + this.$api.files.url(file.parent, file.filename),
        accept: file.mime,
        multiple: false
      });
    },
    async sort(items) {
      if (this.sortable === false) {
        return false;
      }

      this.isProcessing = true;

      items = items.map(item => {
        return item.id;
      });

      try {
        await this.$api.patch(this.apiUrl + "/files/sort", {
          files: items,
          index: this.pagination.offset
        });
        this.$store.dispatch("notification/success", ":)");
        this.$events.$emit("file.sort");

      } catch (error) {
        this.$store.dispatch("notification/error", error.message);

      } finally {
        this.isProcessing = false
      }
    },
    update() {
      this.$events.$emit("model.update");
    },
    upload() {
      if (this.add === false) {
        return false;
      }

      this.$refs.upload.open({
        ...this.add,
        url: this.$urls.api + "/" + this.add.api
      });
    }
  }
};
</script>

<style>
.k-files-section[data-processing] {
  pointer-events: none;
}
</style>
