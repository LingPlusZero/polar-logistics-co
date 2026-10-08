# 靜態 Demo 策略

## 目標
佈署到 github pages，

## 作法
前端統一經過一個 api 服務，依環境變數切換實作，元件只認識 api 介面，不知道資料從哪來。

## env
- .env：預設值（正常模式，打 Laravel API）
- .env.development：開發時強制 Demo 模式（API 端點完成前先吃 JSON；要接真實 API 時改成 false）
- .env.demo：GitHub Pages 建置

## 注意事項
- Demo 模式下存 localStorage 或 sessionStorage，重新整理就重置。
- 頁面頂端固定顯示橫幅：「Demo 模式：資料僅存在你的瀏覽器，不會送出」。

## GitHub Pages 注意事項
- 避免重新整理 404
- 所有前端部署到同一個 Pages，用子路徑區分
- README 放連結、截圖與「如何本機用 Docker 跑完整版」

## 部署流程（GitHub Actions）
- workflow：`.github/workflows/deploy-pages.yml`，push 到 `main` 或手動觸發（workflow_dispatch）
- 建置腳本：`scripts/build-pages.sh`，依序建置各前端並輸出到 `_site/`
  - 官網在站台根目錄（`/<倉庫名稱>/`）
  - 精靈管理系統預計在 `/<倉庫名稱>/admin/`，目前在腳本中註解，完成後開啟，並同步開啟官網頁尾「員工專區」（`SiteFooter.vue` 的 `IS_ADMIN_ENABLED`）
- 子路徑由環境變數 `VITE_BASE` 決定（workflow 以倉庫名稱帶入，優先於 `.env.demo`），倉庫改名不需改檔案
- 每次建置使用 `npm ci`，依 `package-lock.json` 安裝
- 路由使用 hash history，重新整理不會 404
- 本機模擬：`PAGES_BASE=/polar-logistics-co sh scripts/build-pages.sh`（需 Node 22），產物在 `_site/`
- 首次啟用：GitHub 倉庫 Settings → Pages → Source 選「GitHub Actions」
