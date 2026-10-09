# api

## 規範
- api 要有測試
- 檢查參數和請求來源，避免 XSS、injection、CSRF 等資安風險
- RESTful API
- 實作後補上 api url 和 規格

## 資安現況
- 參數：FormRequest 驗證型別與長度；Eloquent 參數綁定，無手寫 SQL（injection）
- XSS：API 只回 JSON，前端用 Vue 文字插值（自動跳脫），不使用 `v-html`
- CSRF：純 API、無 session/cookie，不適用；之後若改用 cookie 驗證需重新評估
- 流量：`throttleApi()`，每個 IP 每分鐘 60 次
- 權限：career 的寫入（POST/PUT/DELETE）需登入且有 `career.manage`（人力資源部）：路由掛 `AuthenticateElf` + `RequirePermission:career.manage`，`CareerRequest::authorize` 再檢查一次；GET 公開（官網使用）。新增需權限的 api 照這個模式
- 登入：權杖只存雜湊；登入端點獨立限流（每 IP 每分鐘 10 次）；純 Bearer 權杖、不用 cookie，不受 CSRF 影響

## 清單
- JSON 欄位一律 camelCase，成功回傳不包 `data`；驗證失敗回 422，找不到回 404

### api/ping 健康檢查
- GET `/api/ping` → `{"status":"ok"}`（測試：tests/Feature/PingTest.php）

### api/career/ 職缺列表
- 欄位：`id`、`title`（必填，≤100）、`departmentId`（選填，須為既有部門 id，空值＝不限部門）、`department`（唯讀，部門名稱，不限部門時為 null）、`description`（必填，≤5000）、`requirements`（必填，≤5000）、`benefits` 福利（選填，≤2000）、`promotion` 轉正機會（選填，≤5000）、`note` 備註（選填，≤2000）
- `description`、`requirements`、`promotion` 為條列欄位：一行一項，以換行字元（`\n`）分隔，前端轉成清單
- 影響欄位：職缺名稱、職缺部門、工作內容、任職資格、福利、轉正機會、備註
- GET `/api/career` 職缺清單 → 200，陣列，依 id 排序（公開）
- 以下寫入需帶 `Authorization: Bearer <token>` 且有 `career.manage`；未登入回 401，沒有權限回 403
- POST `/api/career` 新增職缺 → 201，回傳該筆
- PUT `/api/career/{id}` 編輯職缺 → 200，回傳該筆（需帶完整欄位）
- DELETE `/api/career/{id}` 刪除職缺 → 204
- 寫入時只送 `departmentId`，不存在的部門回 422
- 測試：tests/Feature/CareerTest.php（含未登入 401、非人力資源部 403）、CareerSeederTest.php

### api/department 部門
- 欄位：`id`、`name`、`duty`（工作內容，可為 null）
- GET `/api/department` 部門清單 → 200，陣列，依 id 排序（目前唯讀）
- 資料由 `DepartmentSeeder` 依 docs/brand.md 建立
- 測試：tests/Feature/DepartmentTest.php

### api/elf 精靈名冊
- 全部端點需帶 `Authorization: Bearer <token>` 且有 `elf.roster`（人力資源部）；未登入回 401，沒有權限回 403
- 欄位：`id`、`number`（精靈編號，`E` + 至少 3 位數字，唯一，新增時由系統自動產生）、`name`（≤50）、`departmentId`（必填，須為既有部門）、`department`（唯讀，部門名稱）、`rank`（職稱：實習精靈／正式精靈／資深精靈／部長／副聖誕老人）、`hiredAt`（到職日 `YYYY-MM-DD`，不可晚於今天）、`status`（正常／請假／可能失蹤）、`note`（選填，≤500）；不回傳密碼與權杖，年資不回傳（由 `hiredAt` 計算）
- 不可修改欄位：`number`、`hiredAt`（年資由它而來）。`number` 新增與修改都不接受（送來也忽略），新增時取 E 開頭編號的最大數字 + 1（`Elf::nextNumber()`，至少 3 位數，在交易內計算）；`hiredAt` 只在 POST 驗證，PUT 即使帶了也忽略
- `status` 的「請假」是有請假申請且正值假期才會顯示，不能手動設定：新增與修改只接受「正常」「可能失蹤」，傳「請假」回 422；目前為請假狀態的精靈修改時不驗證也不更動 `status`。請假單功能完成後，由請假單算出（目前尚未實作）
- GET `/api/elf` 名冊（搜尋、篩選、排序、分頁都在後端）→ 200 `{ items, total, page, perPage, lastPage }`（不包 `data`）；`page` 超出範圍時以資料庫分頁行為回傳該頁（可能為空）
  - 查詢參數（皆選填）：`search`（精靈編號或姓名模糊搜尋，≤50，`%` `_` 當一般字元）、`departmentId`、`sort`（`number`｜`department`｜`seniority`，預設 `number`）、`order`（`asc`｜`desc`，預設 `asc`）、`page`（預設 1）、`perPage`（預設 10，1–50）；參數不合法回 422
  - 年資越高＝到職日越早，所以 `sort=seniority&order=desc` 是年資高到低；同分時以編號升冪，分頁順序才穩定
- POST `/api/elf` 新增 → 201，回傳該筆；新精靈使用預設密碼（`Elf::DEFAULT_PASSWORD`），需自行修改
- PUT `/api/elf/{id}` 修改 → 200，回傳該筆（需帶 `name`、`departmentId`、`rank`、`status`，`note` 選填）
- DELETE `/api/elf/{id}` 刪除 → 204；刪除自己回 422 `{"message":"不能刪除自己"}`
- 測試：tests/Feature/ElfTest.php

### api/complaint 精靈被申訴紀錄／我要申訴
- 全部端點需帶 `Authorization: Bearer <token>`；未登入回 401，沒有權限回 403
- 欄位（紀錄）：`id`、`elfNumber`／`elfName`（被申訴人的編號與姓名）、`filedAt`（申訴日期 `YYYY-MM-DD`）、`reason`（申訴事由，≤500）、`status`（處理中／已結案）、`resolution`（後續處理，結案時必填，≤2000，未結案為 null）、`handler`（處理人姓名，結案者，未結案為 null）；申訴人（`complainant_id`）只存不回傳
- POST `/api/complaint` 我要申訴（`complaint.file`，所有人；每 IP 每分鐘 10 次）→ 201 `{ id, elfNumber, elfName }`；body `{ elfNumber, reason }`，`elfNumber` 是被申訴人編號；申訴日期取當天、狀態預設處理中、申訴人為登入者；編號不存在或申訴自己回 422（`errors.elfNumber`）。因申訴人不一定能看紀錄，只回確認用的最少資訊
- GET `/api/complaint` 紀錄清單（`elf.complaint`，人力資源部）→ 200 `{ items, total, page, perPage, lastPage }`；查詢參數 `status`（處理中｜已結案）、`page`、`perPage`（預設 10，1–50）；固定依申訴日期新到舊，同日依 id
- POST `/api/complaint/{id}/close` 結案（`elf.complaint`）→ 200，回傳該筆；body `{ resolution }`（必填）；處理人由登入者自動帶入；已結案再結案回 409；已結案不能更改，也沒有修改、刪除端點
- 資料表 `complaint`：被申訴人刪除時紀錄一併刪除，申訴人、處理人刪除時保留紀錄、欄位設為空
- 資料由 `ComplaintSeeder` 建立（表內已有資料就不灌入）：E016 夜櫻與 E017 晨露一直用小事投訴對方，共 8 筆，4 筆已結案、4 筆處理中
- 測試：tests/Feature/ComplaintTest.php

### api/statics/annual 年度統計
- 欄位：`year`、`giftsDelivered`（份）、`growthRate`（%）、`onTimeRate`（%）、`completeRate`（%）、`feedbackRate`（%）、`note`（可為 null）
- GET `/api/statics/annual?limit=10` 年度統計 → 200，取最新 `limit` 年（預設 10，1–50），由舊到新排序
- 資料由 `AnnualStaticSeeder` 依 docs/website.md 的產生規則建立（固定亂數種子，每次結果相同；2025 年固定為首頁數據列的數字）
- 測試：tests/Feature/AnnualStaticTest.php

### api/auth 登入／登出
- 一律走 HTTPS（開發：`https://localhost:8443`，自簽憑證）；正式環境非 HTTPS 的 auth 請求回 403，見 architecture「傳輸加密」
- 認證方式：`POST /api/auth/login` 取得權杖，之後在 header 帶 `Authorization: Bearer <token>`；權杖由 `AuthenticateElf` middleware 驗證
- 權杖：隨機 60 字元，資料庫只存 sha256 雜湊（`elves.api_token`）；每名精靈同時只有一組有效權杖，重新登入或登出會讓舊的失效
- 閒置自動登出：`elves.api_token_used_at` 記錄權杖最後使用時間（每次請求，至少隔 60 秒才寫入一次）；`AuthenticateElf` 發現閒置 ≥ `Elf::IDLE_TIMEOUT_MINUTES`（30 分鐘）就清掉權杖並回 401 `{ message, reason: "idle" }`，前端據此提示「閒置自動登出」；測試見 AuthTest
- 密碼：以 bcrypt 雜湊儲存並比對，開發用預設密碼 `1qaz@WSX3edc`（見 db-scheme）；登入時只檢查有填、≤72，密碼規則（至少 12 字元，且各一個大寫、小寫、數字、特殊符號）留給設定新密碼時檢查。Demo 前端（demoClient）例外，密碼就是自己的精靈編號
- POST `/api/auth/login` 登入 → 200 `{ token, elf }`；`elf` 欄位見下方 me；帳號不存在或密碼錯誤都回 401 `{"message":"帳號或密碼錯誤"}`（不透露帳號是否存在）、缺欄位回 422；每個 IP 每分鐘 10 次
- POST `/api/auth/logout` 登出（需帶權杖）→ 204，權杖立即失效
- GET `/api/auth/me` 目前登入者（需帶權杖）→ 200 `{ id, number, name, departmentId, department, rank, permissions }`；未登入或權杖失效回 401
- PUT `/api/auth/password` 修改密碼（需帶權杖、`password.change`）→ 204；body `{ oldPassword, newPassword, newPasswordConfirmation }`；驗證失敗回 422 `{ message, errors: { 欄位: [訊息] } }`（舊密碼錯誤也是 422，不用 401，避免前端誤判登入失效）；每個 IP 每分鐘 10 次
  - 新密碼規則：至少 12 字元（≤72），且各一個大寫、小寫、數字、特殊符號，不可與舊密碼相同，兩次輸入需一致
  - 修改後該精靈所有權杖立即失效（包含目前這一組），需用新密碼重新登入
- 權限（`permissions`，由 `Elf::permissions()` 依職級與部門計算，前端選單依此顯示）：
  - 所有人：`leave.apply`、`password.change`、`complaint.file`（我要申訴）
  - 部長、副聖誕老人：`leave.review`
  - 人力資源部：`elf.roster`、`elf.leave`、`elf.complaint`、`elf.attendance`、`career.manage`、`reindeer.manage`
  - 馴鹿管理部：`reindeer.manage`
- 測試：tests/Feature/AuthTest.php（含修改密碼）
