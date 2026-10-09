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
- 最後出勤日欄位在年資之後，沒有出勤紀錄顯示「—」；Demo 的名冊快照不含這個欄位，`demoClient` 輸出時（列表、新增、修改）才依出勤紀錄算出（`withLastAttended`）
- Demo：`demoClient.elf.list` 在前端模擬後端的篩選、排序、分頁；資料 `shared/api/demo/data/roster.json`（含到職日、狀態、備註）由 ElfSeeder 與 `ElfResource` 匯出，寫入存 sessionStorage（`demo:roster`），ElfSeeder 變動後需重新匯出

## 版面與選單
- 寬螢幕（≥48em）側欄固定在左側；窄螢幕改為頂列按鈕展開；固定元素以 `--banner-height` 避開 Demo 橫幅
- 側欄選單過長時仍可捲動，但隱藏捲軸（`scrollbar-width: none`）
- 選單：手風琴（同時只展開一組，`SideMenu.vue`），進入群組內頁面時自動展開；首頁、群組標題、子項目共用同一套連結樣式（目前頁面用紅色左線標示）；展開動畫用 grid 列高 `0fr → 1fr`，收合時 `inert`

## 首頁
- 倒數：共用 `shared/composables/useDeadlineCountdown.ts`（官網數據列也使用），期限為 12/25 零點
- 深藍漸層 hero + `SnowBackdrop.vue`（canvas 雪花與右下角的小聖誕樹〔單色線條、紅色星星，吊飾緩慢閃爍，畫面寬度 <320px 不畫〕，依面積決定數量、以時間差推進、分頁隱藏時暫停、`prefers-reduced-motion` 時只畫靜態畫面）
- 磨砂玻璃倒數方塊（數字每次更新有進場動畫）+ 依權限產生的「常用功能」捷徑

## 精靈被申訴紀錄與我要申訴
- 紀錄頁 `views/ComplaintRecordsView.vue`（`elf.complaint`）：搜尋（被申訴人編號/姓名，300ms debounce）、部門篩選（被申訴人所屬部門）、日期篩選（申訴日期，預設最近一週）、狀態篩選 + 後端分頁（每頁 10 筆，新的在前）；搜尋、部門、日期這組篩選列與出勤紀錄共用 `components/RecordFilters.vue`，狀態下拉由 slot 放進去，預設日期由 `utils/dateRange.ts` 的 `lastWeekRange()` 提供；只有「處理中」顯示「結案」按鈕，已結案不能更改（API 也不提供修改與刪除）
- 結案視窗（`ModalDialog`）：後續處理說明必填，處理人只顯示由系統帶入的登入者姓名，不讓使用者填；遇到 409（已被別人結案）會重新載入清單
- 我要申訴頁 `views/ComplaintFileView.vue`（`complaint.file`，所有人）：只填被申訴人編號與事由；送出後用 API 回傳的姓名顯示「已送出對 ○○（編號）的申訴」，讓申訴人確認沒輸錯
- 分頁元件抽成 `components/PaginationBar.vue`（名冊、申訴紀錄共用）；表格、提示、文字連結等清單頁共用樣式放在 `src/styles/controls.css`
- Demo：`demoClient.complaint` 在前端模擬搜尋、篩選與分頁；資料 `shared/api/demo/data/complaint.json`（由 `ComplaintSeeder` 匯出）以 `daysAgo`（距今幾天前）存日期，載入時換算成實際日期，所以預設最近一週永遠有資料；寫入存 sessionStorage（`demo:complaint`），ComplaintSeeder 變動後需重新匯出；`elf.json` 的權限已加上 `complaint.file`

## 請假申請與審核
- 申請頁 `views/LeaveApplyView.vue`（`leave.apply`，所有人）：選假別（下拉選項帶天數）與請假起日，迄日由 `leaveEndDate` 即時算出並顯示；表單的假別、起日與送出按鈕水平並排（≥48em，窄螢幕直向堆疊），日期提示與錯誤統一放在整列下方；下方「我的請假單」列表 + 審核結果與申請日期篩選（起迄皆可留空，預設看全部，可清除）+ 後端分頁（每頁 10 筆）
- 旺季（12 月）：頁面頂端平時就以紅字顯示「12 月是旺季，不能請假，大家一起撐下去！」，今天在 12 月時表單停用；選的請假期間只要有一天落在 12 月（`touchesPeakSeason`）也會在欄位下方提示並停用送出；後端仍會再驗證（文案在 `shared/api/leave.ts` 的 `PEAK_SEASON_MESSAGE`，與後端 `LeaveApplyRequest` 同步）
- 假別、天數、旺季規則放在 `shared/api/leave.ts`（`LEAVE_TYPES` 與後端 `LeaveType` 同步；日期運算用本地日期的年月日，避免 `YYYY-MM-DD` 被當 UTC 差一天）
- 審核頁 `views/LeaveReviewView.vue`（`leave.review`，部長與副聖誕老人；副聖誕老人沒有上層，自己的假單由自己審）：預設只看「審核中」，可切換全部／核准／駁回；頁面上方標示審核範圍；「核准」直接送出，「駁回」開 `ModalDialog` 填必填的駁回理由；審核人與審核日由系統帶入；已審核的不再顯示操作按鈕，遇到 409（已被別人審核）會重新載入清單
- 精靈請假紀錄 `views/LeaveRecordsView.vue`（`elf.leave`，人力資源部，唯讀）：搜尋（申請人編號/姓名，300ms debounce）、部門篩選、申請日期篩選（預設最近一週，可清除）、審核結果篩選 + 後端分頁（每頁 10 筆）；搜尋、部門、日期用共用的 `components/RecordFilters.vue`，審核結果下拉由 slot 放進去；欄位為精靈編號、申請日期、假別、審核日期、審核結果、駁回理由；Demo 由 `demoClient.leave.records` 模擬
- 審核結果標籤樣式（`.status--pending`／`--approved`／`--rejected`）在 `src/styles/controls.css`，除了顏色文字本身也標示
- 名冊的「請假」狀態由請假單決定，不需要前端另外處理
- Demo：`demoClient.leave` 在前端模擬申請驗證（起日、旺季、重疊）、審核範圍與審核；資料 `shared/api/demo/data/leave.json`（與 `LeaveRequestSeeder` 對應）以 `startAgo`／`appliedAgo`／`reviewedAgo`（距今幾天前，負數為未來）存日期，載入時換算，寫入存 sessionStorage（`demo:leave`）；名冊的「請假」狀態由 `withLastAttended` 依假單算出，LeaveRequestSeeder 變動後 JSON 需同步修改
- 尚未實作：馴鹿代請假（照護專員可選替自己或馴鹿請假），待動力單位管理完成
- 後端為準：權杖閒置 30 分鐘即失效（`AuthenticateElf`，見 `docs/api.md`），前端另有計時器讓畫面即時反應，兩邊的 30 分鐘要同步（`Elf::IDLE_TIMEOUT_MINUTES`、`useIdleLogout.ts` 的 `IDLE_TIMEOUT_MS`）
- `composables/useIdleLogout.ts`（掛在 `AdminLayout`）：監聽 pointerdown／keydown／wheel／touchstart 記錄最後操作時間，每 15 秒用時間戳記比對（不用單一 30 分鐘 setTimeout，因背景分頁計時器會被延後），切回分頁時立刻檢查；超過就呼叫登出 API 後導回登入頁
- 使用者有操作但沒打 API 時，後端看不到活動，所以每 5 分鐘（且期間有操作）呼叫一次 `auth.me()` 續命
- 被動登出統一走 `composables/useSessionExpiry.ts` 的 `expireSession(notice)`：清本機登入狀態、導向 `/login?notice=...&redirect=目前頁面`，重新登入後回到原頁面
- 已登入時任何 API 回 401：`httpClient` 呼叫 `notifyUnauthorized(reason)`（`shared/api/session.ts`，`main.ts` 註冊處理）；`reason === 'idle'` 顯示 `idle-timeout`，其餘顯示 `session-expired`；登入端點本身的 401（帳密錯誤）不算
- 登入頁提示文案在 `LoginView.vue` 的 `NOTICES`（`password-changed`、`idle-timeout`、`session-expired`）
- Demo 模式沒有後端，只有前端計時器作用

## 精靈出勤紀錄
- 頁面 `views/AttendanceView.vue`（`elf.attendance`）：唯讀列表 + 後端分頁（每頁 10 筆）；搜尋（精靈編號/姓名，300ms debounce）、部門與日期篩選用共用的 `components/RecordFilters.vue`（預設最近一週：今天往前 6 天到今天，用瀏覽器本地日期，見 `utils/dateRange.ts`；起、迄皆可留空，「清除日期」會看到全部；改變就回第 1 頁，迄日 `min` 限制為起日。預設只是前端帶的查詢條件，API 本身沒有預設日期範圍）；排序用表頭按鈕（精靈編號、上班時間、下班時間、工作時數，四欄樣式一致；一開始都未標示排序，資料依預設的上班時間新到舊，點上班時間即回到此順序並標示），點同一欄切換升降冪，換欄時上班、下班時間先新到舊，編號與工作時數先升冪（工時短的在前，方便找出不足 8 小時的日子）
- 工作時數不足 8 小時（`workMinutes < 480`）的整列背景 `#fff6d0`；表格上方放圖例說明底色意義，避免只靠顏色傳達
- Demo：不存快照，`shared/api/demo/attendance.ts` 的 `generateAttendance` 依「今天」往前產生（同一個分頁只算一次，唯讀）；規則與 `AttendanceSeeder` 完全一致（含與 PHP 相同的 crc32 雜湊、時間範圍與失蹤精靈的最後出勤日），改一邊要改另一邊，所以 Demo 與真實資料在同一天看到的內容相同，GitHub Pages 上也不會過期
