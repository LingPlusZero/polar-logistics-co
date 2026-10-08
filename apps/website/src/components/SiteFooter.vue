<script setup lang="ts">
// 員工專區連到精靈管理系統，網址依環境設定（見 .env）
const adminUrl = import.meta.env.VITE_ADMIN_URL
</script>

<template>
  <footer class="site-footer">
    <div class="container">
      <p>極地物流股份有限公司｜統一編號 25122400｜客服專線 0800-024-1224</p>
      <p>總部：北緯 90 度 0 分，北極點</p>
      <p>© 1823–2025 Polar Logistics Co., Ltd. 版權所有</p>
      <a class="site-footer__link" :href="adminUrl">員工專區</a>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  padding: 2.5rem 0;
  font-size: 0.875rem;
  color: var(--color-ice-dark);
  background: var(--color-navy);
}

.site-footer p {
  margin-bottom: 0.25rem;
}

.site-footer__link {
  display: inline-block;
  margin-top: 1rem;
  color: var(--color-ice);
}
</style>
