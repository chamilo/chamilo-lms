<template>
  <footer class="footer mt-auto px-4">
    <b-row
      align-v="center"
      class="justify-content-lg-between"
    >
      <b-col lg="6">
        <div class="text-center text-lg-left text-muted">
          © {{ year }}
          <a
            href="https://www.chamilo.com"
            class="font-weight-bold ml-1"
            target="_blank"
          >
            Chamilo
          </a>
        </div>
      </b-col>
      <b-col lg="6">
        <b-nav
          align="center"
          class="nav-footer justify-content-lg-end"
        >
          <b-nav-item
            href="#"
            target="_blank"
          >
            About
          </b-nav-item>
        </b-nav>
      </b-col>
    </b-row>
  </footer>
</template>

<script>
export default {
  data() {
    return {
      year: new Date().getFullYear()
    };
  }
};
</script>
<style></style>
