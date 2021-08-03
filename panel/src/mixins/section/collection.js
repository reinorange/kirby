export default {
  inheritAttrs: false,
  props: {
    blueprint: String,
    column: String,
    empty: String,
    headline: String,
    help: String,
    layout: String,
    link: String,
    max: Number,
    min: Number,
    parent: String,
    name: String,
    size: String,
    timestamp: Number,
    sortable: Boolean,
  },
  data() {
    return {
      isProcessing: false,
    };
  },
  methods: {
    items(data) {
      return data;
    },
    paginate(pagination) {
      this.$reload({
        query: {
          [`${this.name}[page]`]: pagination.page
        }
      });
    }
  }
};
