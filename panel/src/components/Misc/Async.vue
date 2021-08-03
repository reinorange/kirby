<script>
export default {
  props: {
    endpoint: String
  },
  data() {
    return {
      isLoading: true,
      error: null,
      data: {}
    }
  },
  watch: {
    endpoint() {
      this.fetch();
    },
    "$view.timestamp"() {
      this.fetch();
    },
  },
  created() {
    this.fetch();
  },
  methods: {
    async fetch() {
      this.isLoading = true

      try {
        if (this.endpoint) {
          this.data  = await this.$request(this.endpoint);
          this.error = false
        }

      } catch (e) {
        this.data = {}
        this.error = e

      } finally {
        this.isLoading = false
      }
    },
    reload() {
      this.fetch();
    }
  },
  render() {
    if (this.isLoading && this.$scopedSlots.loading) {
      return this.$scopedSlots.loading();
    }

    if (this.error && this.$scopedSlots.error) {
      return this.$scopedSlots.error({
        error: this.error,
        reload: this.reload
      });
    }

    return this.$scopedSlots.default({
      isLoading: this.isLoading,
      response: this.data,
      reload: this.reload
    });
  },
}
</script>
