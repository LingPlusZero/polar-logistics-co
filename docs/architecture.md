# 架構與技術細節

專案結構與常用指令見 `CLAUDE.md`，此文件記錄實作層面的細節。

## Docker
- 服務：`mysql`（8.4）、`php`（php:8.4-fpm-alpine，`docker/php/Dockerfile`）、`nginx`（1.27-alpine）、`website`、`admin`（node:22-alpine，跑 vite dev server）
- 對外埠：官網 5173、管理系統 5174（HTTPS）、API（nginx）8443（HTTPS）與 8080（只轉址到 8443）、MySQL 33060
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
- 權限 middleware：`AuthenticateElf`（辨識身分）、`RequirePermission:<權限鍵>`（檢查 `Elf::hasPermission`）、`RequireSecureConnection`（正式環境限 HTTPS）
- 寫入驗證用 FormRequest（`CareerRequest`）；對外 camelCase 與資料表 snake_case 的轉換在 request 內
- 測試用 sqlite in-memory（`phpunit.xml`），`RefreshDatabase`
- Seeder（啟動時自動執行）：
  - `AnnualStaticSeeder`：固定亂數種子，每次結果相同；2025 年對齊官網首頁數據列
  - `DepartmentSeeder`：以 name 做 updateOrCreate（含董事會）
  - `CareerSeeder`：表內已有資料就不灌入，避免還原使用者刪除或改名的職缺；須排在 DepartmentSeeder 之後

## 前端（website、admin）
- Vue 3 + Vue Router 4 + Vite + TypeScript；官網另用 echarts
- `vite.config.ts`
  - `/api` proxy 到 `API_PROXY_TARGET`（docker 內為 `https://nginx`，本機預設 `https://localhost:8443`；後端是自簽憑證，proxy 設 `secure: false`）
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

## apps/admin（精靈管理系統）
- 路由：`/login`（公開）與 `/`（`AdminLayout` 側欄 + 內容區）；`beforeEach` 先 `restore()` 確認權杖，未登入導向 `/login?redirect=`，無權限導回首頁
- 選單與路由共用 `src/config/menu.ts`：新增頁面只需在這裡加一筆（`path`、`permission`、`component`）；未提供 `component` 的頁面先顯示 `PlaceholderView`（建置中）；群組內沒有可見項目時整組隱藏
- 登入狀態：`src/composables/useAuth.ts`（模組層級 `ref`）；權杖與個人資料存 sessionStorage（`shared/api/session.ts`），關閉分頁即登出
- 權限鍵由後端回傳（`permissions`），Demo 模式讀 `shared/api/demo/data/elf.json`（由 `ElfProfileResource` 匯出，ElfSeeder 或權限規則變動後需重新匯出）
- 密碼規則在 `shared/api/password.ts`（至少 12 字元 + 大小寫數字特殊符號），給修改密碼頁使用；登入頁不顯示也不檢查規則，以後端回覆為準
- 真實 API 帳號：E001–E022，預設密碼 `1qaz@WSX3edc`；Demo 模式（`.env.demo`、`.env.development` 設 true）密碼輸入自己的帳號（`demoClient.auth.login` 比對）。`.env.development` 目前為 `false`（接真實 API）
- 版面：寬螢幕（≥48em）側欄固定在左側；窄螢幕改為頂列按鈕展開；固定元素以 `--banner-height` 避開 Demo 橫幅
- 側欄選單過長時仍可捲動，但隱藏捲軸（`scrollbar-width: none`）
- 選單：手風琴（同時只展開一組，`SideMenu.vue`），進入群組內頁面時自動展開；首頁、群組標題、子項目共用同一套連結樣式（目前頁面用紅色左線標示）；展開動畫用 grid 列高 `0fr → 1fr`，收合時 `inert`
- 首頁倒數：共用 `shared/composables/useDeadlineCountdown.ts`（官網數據列也使用），期限為 12/25 零點
- 首頁：深藍漸層 hero + `SnowBackdrop.vue`（canvas 雪花與右下角的小聖誕樹〔單色線條、紅色星星，吊飾緩慢閃爍，畫面寬度 <320px 不畫〕，依面積決定數量、以時間差推進、分頁隱藏時暫停、`prefers-reduced-motion` 時只畫靜態畫面）+ 磨砂玻璃倒數方塊（數字每次更新有進場動畫）+ 依權限產生的「常用功能」捷徑

## 傳輸加密（HTTPS）
- 密碼等機敏資訊不可明碼傳輸：登入只走 `POST` body（不放網址），整段路徑都加密
  - 瀏覽器 → admin dev server：`@vitejs/plugin-basic-ssl`（只在 `serve` 啟用，自簽憑證），網址 `https://localhost:5174`
  - dev server → API：proxy 到 `https://nginx`
  - 瀏覽器直連 API：`https://localhost:8443`；nginx 80 埠只做 301 轉址
- 憑證：docker-compose 的 `certs` 服務（`alpine/openssl`）在 `dev-certs` volume 產生自簽憑證（已存在就略過），nginx 唯讀掛載；第一次開網頁瀏覽器會警告，需手動信任／繼續前往
- nginx 以 `fastcgi_param HTTPS on` 告知 Laravel 是 HTTPS 請求
- 後端 `RequireSecureConnection` middleware：正式環境（`APP_ENV=production`）收到非 HTTPS 的登入、登出、me 請求回 403；開發環境不強制
- 不在前端自行雜湊密碼來取代 HTTPS：雜湊值等同密碼，被攔截一樣能重放登入；正式環境需改用正式憑證並加上 HSTS
- 重置憑證：`docker compose down -v` 會一併清除，下次啟動重新產生
