<!--------------------------------
 - @Description: 底部
 - @Author: Ronnie Zhang
 - @LastEditor: TNT-12M
 - @LastEditTime: 2026/10/05
 --------------------------------->

<template>
  <footer class="footer-wrap">
    <div class="footer-content">
      <div class="copyright">
        <span>© {{ year }} 学境・StudyScape</span>
        <span class="dot">·</span>
        <span>开发者：TNT-12M</span>
      </div>
      <a
        class="github-link"
        href="https://github.com/TNT-12M/StudyScape"
        target="_blank"
        rel="noopener noreferrer"
        title="GitHub 仓库"
      >
        <i class="i-mdi:github github-icon" />
        <span>GitHub</span>
      </a>
    </div>
  </footer>
</template>

<script setup>
const year = new Date().getFullYear()
</script>

<style scoped>
.footer-wrap {
  padding: 16px 24px;
  color: rgba(150, 150, 150, 0.8);
  font-size: 13px;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  background: transparent;
}
.footer-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  max-width: 100%;
}
.copyright {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.dot {
  opacity: 0.5;
}
.github-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: inherit;
  text-decoration: none;
  padding: 4px 10px;
  border-radius: 6px;
  transition: all 0.2s ease;
}
.github-link:hover {
  color: #333;
  background: rgba(0, 0, 0, 0.06);
}
.github-icon {
  font-size: 18px;
  line-height: 1;
}
@media (max-width: 640px) {
  .footer-wrap {
    padding: 12px 16px;
    font-size: 12px;
  }
  .footer-content {
    justify-content: center;
  }
}
</style>
