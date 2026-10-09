# 精靈管理系統實作細節

功能與設定以 `docs/admin.md` 為準，此文件只記技術做法。API 規格見 `docs/api.md`，傳輸加密見 `docs/architecture.md`。

## 路由與選單
- 路由：`/login`（公開）與 `/`（`AdminLayout` 側欄 + 內容區）；`beforeEach` 先 `restore()` 確認權杖，未登入導向 `/login?redirect=`，無權限導回首頁
- 選單與路由共用 `src/config/menu.ts`：新增頁面只需在這裡加一筆（`path`、`permission`、`component`）；未提供 `component` 的頁面先顯示 `PlaceholderView`（建置中）；群組內沒有可見項目時整組隱藏

## 登入與權限
- 登入狀態：`src/composables/useAuth.ts`（模組層級 `ref`）；權杖與個人資料存 sessionStorage（`shared/api/session.ts`），關閉分頁即登出
- 權限鍵由後端回傳（`permissions`），Demo 模式讀 `shared/api/demo/data/elf.json`（由 `ElfProfileResource` 匯出，ElfSeeder 或權限規則變動後需重新匯出）
- 真實 API 帳號：E001–E022，預設密碼 `1qaz@WSX3edc`；Demo 模式（`.env.demo`、`.env.development` 設 true）密碼輸入自己的帳號（`demoClient.auth.login` 比對）。`.env.development` 目前為 `false`（接真實 API）

## 修改密碼
- 密碼規則在 `shared/api/password.ts`（至少 12 字元 + 大小寫數字特殊符號），與後端 `ChangePasswordRequest` 同步；登入頁不顯示也不檢查規則，以後端回覆為準，規則只用在修改密碼頁（`PasswordView.vue`，即時顯示規則達成狀況）
- 成功後前端呼叫 `logout()` 並導回 `/login?notice=password-changed`（登入頁顯示「密碼已更新，請使用新密碼重新登入。」）；`ApiError.errors` 帶欄位層級的 422 訊息
- Demo 模式改過的密碼存 sessionStorage（`demo:password:<編號>`），登入時以它為準，重新整理不會重置、關閉分頁才重置

## 共用元件與樣式
- 彈出視窗：`components/ModalDialog.vue` 用原生 `<dialog>` + `showModal()`（自帶遮罩、焦點鎖定、Esc 關閉；Esc 以 `cancel` 事件交給父層關閉）
- `components/ConfirmDialog.vue`：`ModalDialog` 的二次確認版（取代 alert / confirm），之後各頁刪除都用它
- 共用按鈕與表單樣式在 `src/styles/controls.css`（`.button`、`.field*`，全域載入，元件內不重複定義）

## 精靈名冊
- 頁面：`views/ElfRosterView.vue`
- 搜尋（300ms debounce）、部門篩選、排序、分頁（每頁 10 筆）都交給後端，`api.elf.list(query)` 回 `Page<Elf>`
- 排序用欄位標題的按鈕（部門、精靈編號、年資），點擊切換升冪／降冪，換條件回第 1 頁
- 以 `requestId` 丟棄過期回應；刪光最後一頁會退回最後一頁
- 新增／修改共用 `components/ElfFormDialog.vue`（有帶 `elf` 為編輯，到職日停用）；精靈編號欄位一律停用，新增時顯示「儲存後自動產生」，由後端產生（Demo 由 `demoClient` 用同樣規則產生）
- 狀態「請假」不能手動改：表單只提供「正常／可能失蹤」，請假中顯示停用欄位且不送出 `status`
- 年資由 `utils/seniority.ts` 以到職日算整月，不存資料庫
- Demo：`demoClient.elf.list` 在前端模擬後端的篩選、排序、分頁；資料 `shared/api/demo/data/roster.json`（含到職日、狀態、備註）由 ElfSeeder 與 `ElfResource` 匯出，寫入存 sessionStorage（`demo:roster`），ElfSeeder 變動後需重新匯出

## 版面與選單
- 寬螢幕（≥48em）側欄固定在左側；窄螢幕改為頂列按鈕展開；固定元素以 `--banner-height` 避開 Demo 橫幅
- 側欄選單過長時仍可捲動，但隱藏捲軸（`scrollbar-width: none`）
- 選單：手風琴（同時只展開一組，`SideMenu.vue`），進入群組內頁面時自動展開；首頁、群組標題、子項目共用同一套連結樣式（目前頁面用紅色左線標示）；展開動畫用 grid 列高 `0fr → 1fr`，收合時 `inert`

## 首頁
- 倒數：共用 `shared/composables/useDeadlineCountdown.ts`（官網數據列也使用），期限為 12/25 零點
- 深藍漸層 hero + `SnowBackdrop.vue`（canvas 雪花與右下角的小聖誕樹〔單色線條、紅色星星，吊飾緩慢閃爍，畫面寬度 <320px 不畫〕，依面積決定數量、以時間差推進、分頁隱藏時暫停、`prefers-reduced-motion` 時只畫靜態畫面）
- 磨砂玻璃倒數方塊（數字每次更新有進場動畫）+ 依權限產生的「常用功能」捷徑
