<template>
  <section class="k-pages-section" :data-processing="isProcessing">
    <header class="k-section-header">
      <k-headline :link="options.link">
        {{ headline }} <abbr v-if="options.min" :title="$t('section.required')">*</abbr>
      </k-headline>

      <k-button-group v-if="add">
        <k-button icon="add" @click="create">
          {{ $t("add") }}
        </k-button>
      </k-button-group>
    </header>

    <template>
      <k-collection
        v-if="data.length"
        :layout="options.layout"
        :help="help"
        :items="items(data)"
        :pagination="pagination"
        :sortable="options.sortable"
        :size="options.size"
        :data-invalid="isInvalid"
        @sort="sort"
        @paginate="paginate"
      />

      <template v-else>
        <k-empty
          :layout="options.layout"
          :data-invalid="isInvalid"
          icon="page"
          @click="create"
        >
          {{ options.empty || $t('pages.empty') }}
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
    </template>
  </section>
</template>

<script>
import CollectionSectionMixin from "@/mixins/section/collection.js";

export default {
  mixins: [CollectionSectionMixin],
  computed: {
    add() {
      return this.options.add && this.$permissions.pages.create;
    }
  },
  methods: {
    create() {
      if (this.add) {
        this.$dialog('pages/create', {
          query: {
            parent: this.options.link || this.parent,
            view: this.parent,
            section: this.name
          }
        });
      }
    },
    items(data) {
      return data.map(page => {
        const isEnabled = page.permissions.changeStatus !== false;

        page.flag = {
          status: page.status,
          tooltip: this.$t("page.status"),
          disabled: !isEnabled,
          click: () => {
            this.$dialog(this.$api.pages.url(page.id) + "/changeStatus");
          }
        };

        page.sortable  = page.permissions.sort && this.options.sortable;
        page.deletable = data.length > this.options.min;
        page.column    = this.column;
        page.options   = this.$dropdown(this.$api.pages.url(page.id), {
          query: {
            view: "list",
            delete: page.deletable,
            sort: page.sortable
          }
        });

        // add data-attributes info for item
        page.data = {
          "data-id": page.id,
          "data-status": page.status,
          "data-template": page.template
        };

        return page;
      });
    },
    async sort(items, event) {
      this.isProcessing = true;

      const item     = items[event.oldIndex];
      const position = event.newIndex + 1 + this.pagination.offset;

      try {
        await this.$api.pages.status(item.id, "listed", position);
        this.$store.dispatch("notification/success", ":)");
      } catch (error) {
        this.$store.dispatch("notification/error", {
          message: error.message,
          details: error.details
        });
      } finally {
        this.isProcessing = false;
        this.$reload();
      }
    }
  }
};
</script>

<style>
.k-pages-section[data-processing] {
  pointer-events: none;
}
</style>
