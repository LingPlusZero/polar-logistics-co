# 架構與技術細節

專案結構與常用指令見 `CLAUDE.md`，此文件記錄實作層面的細節。

## Docker
- 服務：`mysql`（8.4）、`php`（php:8.4-fpm-alpine，`docker/php/Dockerfile`）、`nginx`（1.27-alpine）、`website`、`admin`（node:22-alpine，跑 vite dev server）
- 對外埠：官網 5173、管理系統 5174、API（nginx）8080、MySQL 33060
- MySQL 用 33060：Windows 保留了 3232–3331 等埠段，3306–3399 附近容易綁不上
- MySQL 只綁本機（`127.0.0.1:33060`），避免同一網路的其他裝置連到使用預設密碼的開發資料庫；正式環境需改用強密碼
- vendor、node_modules 放 named volume，避免 Windows bind mount 拖慢速度
- `.gitattributes` 把 `.sh`、`.conf`、`Dockerfile` 固定為 LF，避免 CRLF 讓容器腳本壞掉

## php 容器啟動流程（`docker/php/entrypoint.sh`）
- 沒有 `.env` 就複製 `.env.example`
- 沒有 vendor 就 `composer install`
- 沒有 `APP_KEY` 就 `key:generate`
- `migrate --force`，再 `db:seed --force`（seeder 皆可重複執行）
- 開放 storage、bootstrap/cache 寫入權限，再啟動 php-fpm

## apps/api
- 已移除 views、resources、vite、User 相關檔案，只保留 API；路由在 `routes/api.php`（前綴 `/api`）
- session=array、cache=file、queue=sync；log 輸出到 stderr（`docker compose logs php`）
- `throttleApi()`：依 IP 每分鐘 60 次，限流器定義在 `AppServiceProvider`
- `JsonResource::withoutWrapping()`：回傳不包 `data`；欄位對外一律 camelCase
- 寫入驗證用 FormRequest（`CareerRequest`）；對外 camelCase 與資料表 snake_case 的轉換在 request 內
- 測試用 sqlite in-memory（`phpunit.xml`），`RefreshDatabase`
- Seeder（啟動時自動執行）：
  - `AnnualStaticSeeder`：固定亂數種子，每次結果相同；2025 年對齊官網首頁數據列
  - `DepartmentSeeder`：以 name 做 updateOrCreate
  - `CareerSeeder`：表內已有資料就不灌入，避免還原使用者刪除或改名的職缺；須排在 DepartmentSeeder 之後

## 前端（website、admin）
- Vue 3 + Vue Router 4 + Vite + TypeScript；官網另用 echarts
- `vite.config.ts`
  - `/api` proxy 到 `API_PROXY_TARGET`（docker 內為 nginx 容器，本機預設 localhost:8080）
  - `@shared` alias 指向 `shared/`，`resolve.dedupe: ['vue']`，`server.fs.allow` 開放 shared
  - `usePolling`：Windows bind mount 收不到檔案事件
  - `loadEnv(mode, cwd, '')`：非 `VITE_` 開頭的變數（如 `API_PROXY_TARGET`）也要讀得到
- env
  - `.env`：`VITE_DEMO_MODE=false`
  - `.env.development`：`true`（開發時強制 Demo；要接真實 API 改成 false）
  - `.env.demo`：`true` + `VITE_BASE` 子路徑，`npm run build:demo`
  - `VITE_ADMIN_URL`：官網頁尾「員工專區」連結
- Demo 模式路由改用 hash history，避免 GitHub Pages 重新整理 404
- tsconfig：`paths` 設定 `@shared/*`，並把 `vue` 指向各 app 自己的 node_modules，shared 內的檔案才能 import vue

## shared/
- `styles/`：`tokens.css`（品牌色、字體、`--header-height`、`--banner-height`）、`base.css`
- `components/`：`DemoBanner.vue`（文案照 `docs/demo-strategy.md`）、`BrandLogo.vue`
- `api/`：前端唯一的資料出口
  - `client.ts`：`ApiClient` 介面，元件只認識它
  - `httpClient.ts`：打 Laravel API
  - `demoClient.ts`：讀 `demo/data/*.json`，寫入存 sessionStorage，重新整理就重置；行為與後端對齊（取最新 10 年、依 departmentId 帶出部門名稱）
  - `index.ts`：依 `VITE_DEMO_MODE` 匯出 `api`
  - `demo/data/*.json`：由 API 資料產出；seeder 或 API 欄位變動後需重新匯出
- 新增套件給 shared 使用時，需在各 app 的 vite 與 tsconfig 做同樣的 dedupe / paths 設定
