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
- 欄位：`id`、`number`（精靈編號，`E` + 至少 3 位數字，唯一，新增時由系統自動產生）、`name`（≤50）、`departmentId`（必填，須為既有部門）、`department`（唯讀，部門名稱）、`rank`（職稱：實習精靈／正式精靈／資深精靈／部長／副聖誕老人）、`hiredAt`（到職日 `YYYY-MM-DD`，不可晚於今天）、`status`（正常／請假／可能失蹤）、`note`（選填，≤500）、`lastAttendedAt`（最後出勤日 `YYYY-MM-DD`，唯讀，取該精靈出勤紀錄中最近一筆上班時間的日期，沒有紀錄為 null；列表用 `withMax` 一次查出，不會每列多一次查詢）；不回傳密碼與權杖，年資不回傳（由 `hiredAt` 計算）
- 不可修改欄位：`number`、`hiredAt`（年資由它而來）。`number` 新增與修改都不接受（送來也忽略），新增時取 E 開頭編號的最大數字 + 1（`Elf::nextNumber()`，至少 3 位數，在交易內計算）；`hiredAt` 只在 POST 驗證，PUT 即使帶了也忽略
- `status` 的「請假」是有請假申請且正值假期才會顯示，不能手動設定：新增與修改只接受「正常」「可能失蹤」，傳「請假」回 422；目前為請假狀態的精靈修改時不驗證也不更動 `status`。狀態由請假單算出：核准的假單涵蓋今天就顯示「請假」（`Elf::displayStatus()`；列表用 `withOnLeave` 一次查出），「可能失蹤」優先於請假；資料庫存的 `status` 仍只有「正常」「可能失蹤」
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
- GET `/api/complaint` 紀錄清單（`elf.complaint`，人力資源部）→ 200 `{ items, total, page, perPage, lastPage }`；查詢參數（皆選填）：`search`（被申訴人的精靈編號或姓名模糊搜尋，≤50，`%` `_` 當一般字元）、`departmentId`（被申訴人所屬部門）、`dateFrom`、`dateTo`（以申訴日期篩選，`YYYY-MM-DD`，含當天，迄日早於起日回 422）、`status`（處理中｜已結案）、`page`、`perPage`（預設 10，1–50）；固定依申訴日期新到舊，同日依 id
- POST `/api/complaint/{id}/close` 結案（`elf.complaint`）→ 200，回傳該筆；body `{ resolution }`（必填）；處理人由登入者自動帶入；已結案再結案回 409；已結案不能更改，也沒有修改、刪除端點
- 資料表 `complaint`：被申訴人刪除時紀錄一併刪除，申訴人、處理人刪除時保留紀錄、欄位設為空
- 資料由 `ComplaintSeeder` 建立（表內已有資料就不灌入，因為紀錄可能已被結案，不能重新產生）：E016 夜櫻與 E017 晨露一直用小事投訴對方，共 8 筆，4 筆已結案、4 筆處理中；申訴日期以「距第一次執行當天幾天前」設定（87、86、67、49 天前已結案；12、5、3、1 天前處理中），讓預設的最近一週有 3 筆。已灌過的資料庫日期不會再變，需要時可清空 `complaint` 表重灌
- 測試：tests/Feature/ComplaintTest.php

### api/leave 請假申請／審核
- 全部端點需帶 `Authorization: Bearer <token>`；未登入回 401，沒有權限回 403
- 欄位：`id`、`elfNumber`／`elfName`（申請人）、`leaveType`（普通病假 1 天｜魔力枯竭假 2 天｜被人類目擊後心理創傷假 7 天，`LeaveType` enum）、`days`、`startDate`／`endDate`（`YYYY-MM-DD`，迄日含當天，由起日加假別天數算出）、`appliedAt`（申請日期）、`status`（審核中｜核准｜駁回）、`reviewedAt`、`reviewer`（審核人姓名）、`rejectReason`（駁回理由，駁回時必填）
- POST `/api/leave` 申請（`leave.apply`，所有人；每 IP 每分鐘 10 次）→ 201，回傳該筆；body `{ leaveType, startDate }`，申請日為當天、狀態預設審核中，申請人為登入者
  - 驗證（422，錯誤在 `errors.startDate`）：起日不可早於今天；請假期間只要有一天落在 12 月就不行（旺季，例如 11/28 起請 7 天也不行）；不可與自己既有的假單重疊（審核中、核准都佔用日期，駁回的不算）
- GET `/api/leave/mine` 自己的假單（`leave.apply`）→ 200 `{ items, total, page, perPage, lastPage }`；查詢參數（皆選填）：`status`、`dateFrom`、`dateTo`（以申請日期篩選，`YYYY-MM-DD`，含當天，迄日早於起日回 422）、`page`、`perPage`（預設 10，1–50）；申請日新到舊，同日依 id
- GET `/api/leave/review` 審核範圍內的假單（`leave.review`）→ 同上格式與參數
  - 審核範圍（`LeaveRequest::scopeReviewableBy`）：部長審自己部門的假單（含實習生，不含自己與其他部長）；副聖誕老人審各部長的假單與自己的假單（申請後同樣是審核中，由自己核准或駁回）
- GET `/api/leave/records` 精靈請假紀錄（`elf.leave`，人力資源部）→ 200 `{ items, total, page, perPage, lastPage }`，全部精靈的假單；查詢參數（皆選填，日期與審核結果同 `/api/leave/mine`）：`search`（申請人的精靈編號或姓名模糊搜尋，≤50，`%` `_` 當一般字元）、`departmentId`（申請人所屬部門）、`dateFrom`、`dateTo`、`status`、`page`、`perPage`（預設 10，1–50）；申請日新到舊，同日依 id（`LeaveRecordListRequest` 繼承 `LeaveListRequest`）
- POST `/api/leave/{id}/approve` 核准（`leave.review`）→ 200，回傳該筆；審核人與審核日由系統帶入
- POST `/api/leave/{id}/reject` 駁回（`leave.review`）→ 200，回傳該筆；body `{ reason }`（必填，≤500）
  - 審核與駁回共通：不在審核範圍回 403；已審核過回 409（條件式 `UPDATE ... WHERE status = '審核中'`，同時審核只有先到的成功）；已審核的假單不能更改，也沒有修改、刪除端點
- 馴鹿代請假：照護專員在 POST `/api/leave` 多帶 `reindeerId`（選填，省略＝替自己請假）即可代自己照護的馴鹿請假；只有該馴鹿的照護專員能代請，別人的馴鹿與不存在的馴鹿同樣回 422（`errors.reindeerId`，不透露馴鹿是否存在）。假單的 `elfNumber`／`elfName` 仍是申請人（照護專員），另有 `reindeerNumber`／`reindeerName`（替自己請假為 null）。重疊檢查把精靈自己與每隻馴鹿分開計算；起日、12 月旺季規則相同；審核由照護專員的上層進行（與精靈自己的假單相同）。馴鹿的假單不影響照護專員的「請假」狀態，也不列入 `/api/leave/records`
- 資料由 `LeaveRequestSeeder` 建立的 E011 長青之外，07 雷霆的假單由 `ReindeerSeeder` 建立（見 api/reindeer）
- 資料由 `LeaveRequestSeeder` 建立（表內已有資料就不灌入）：12 筆（核准、駁回、審核中皆有；其中 6 筆是 E011 長青一直請假、一直被駁回，最後一張還在審核中），日期以「距第一次執行當天幾天前」設定；E002 雲杉的心理創傷假涵蓋今天，名冊會顯示「請假」
- 測試：tests/Feature/LeaveTest.php

### api/reindeer 動力單位（馴鹿）管理
- 管理端點需帶 `Authorization: Bearer <token>` 且有 `reindeer.manage`（人力資源部、馴鹿管理部）；未登入回 401，沒有權限回 403
- 欄位：`id`、`number`（編號，兩位數以上，例如 `01`，唯一，新增時由系統自動產生）、`name`（≤50）、`hiredAt`（到職日 `YYYY-MM-DD`，不可晚於今天）、`lastMaintainedAt`（上次保養日期，不可晚於今天）、`nextMaintenanceAt`（下次保養日期，唯讀，上次保養後滿 3 個月，月底不溢位）、`caretakerId`（照護專員，必填，須為馴鹿管理部的精靈）、`caretaker`（唯讀，照護專員姓名）、`note`（選填，≤500）；年資不回傳（由 `hiredAt` 計算）
- 不可修改欄位：`number`、`hiredAt`（年資由它而來）、`nextMaintenanceAt`。`number` 新增與修改都不接受（送來也忽略），新增時取編號的最大數字 + 1（`Reindeer::nextNumber()`，至少 2 位數，在交易內計算）；`hiredAt` 只在 POST 驗證，PUT 即使帶了也忽略
- GET `/api/reindeer` 清單 → 200，陣列（目前只有 9 隻，不分頁、不搜尋）；查詢參數（皆選填）：`sort`（`number`｜`seniority`，預設 `number`）、`order`（`asc`｜`desc`，預設 `asc`）；年資越高＝到職日越早，所以 `sort=seniority&order=desc` 是年資高到低；參數不合法回 422
- POST `/api/reindeer` 新增 → 201，回傳該筆
- PUT `/api/reindeer/{id}` 修改 → 200，回傳該筆（需帶 `name`、`lastMaintainedAt`、`caretakerId`，`note` 選填）
- DELETE `/api/reindeer/{id}` 刪除 → 204；該馴鹿的假單一併刪除
- GET `/api/reindeer/caretakers` 可選的照護專員（`reindeer.manage`）→ 200 `[{ id, number, name }]`，馴鹿管理部的精靈（名冊只有人力資源部能看，所以另開端點給馴鹿管理部使用）
- GET `/api/reindeer/{id}/leave` 該馴鹿的請假記錄（`reindeer.manage`）→ 200，陣列，欄位同 api/leave，申請日新到舊
- GET `/api/reindeer/mine` 登入者擔任照護專員的馴鹿（`leave.apply`，所有人）→ 200 `[{ id, number, name }]`；請假申請頁據此決定是否顯示「請假對象」選項
- 資料由 `ReindeerSeeder` 建立（表內已有資料就不灌入）：9 隻，編號 01–09，名字與備註見 docs/brand.md（01 魯道夫、07 問題單位、09 新一代）；照護專員為馴鹿管理部的 E004、E013、E014；上次保養日以「距第一次執行當天幾天前」設定。07 雷霆由 E013 代請 5 張假單：4 張被 E004 駁回、1 張審核中
- 測試：tests/Feature/ReindeerTest.php

### api/attendance 精靈出勤紀錄
- 沒有真正的打卡機制，只有列表（沒有新增、修改、刪除）；需帶 `Authorization: Bearer <token>` 且有 `elf.attendance`（人力資源部），未登入回 401，沒有權限回 403
- 欄位：`id`、`elfNumber`／`elfName`、`clockIn`（上班時間）、`clockOut`（下班時間）（皆為本地時間 `YYYY-MM-DD HH:mm`，不含時區）、`workMinutes`（工作時數，單位分鐘，由上下班時間計算；前端以 480 判斷「不足 8 小時」）
- GET `/api/attendance` → 200 `{ items, total, page, perPage, lastPage }`；查詢參數（皆選填）：`search`（精靈編號或姓名模糊搜尋，≤50，`%` `_` 當一般字元）、`departmentId`（精靈所屬部門）、`dateFrom`、`dateTo`（以上班日期篩選，`YYYY-MM-DD`，含當天，迄日早於起日回 422）、`sort`（`clockIn`｜`clockOut`｜`number`｜`workMinutes`，預設 `clockIn`）、`order`（`asc`｜`desc`，預設 `desc`）、`page`、`perPage`（預設 10，1–50）；同分時依上班時間新到舊、id 排序讓分頁穩定。日期用上班時間的範圍比對（用得到索引）；工時排序在 SQL 內用 `TIMESTAMPDIFF`（測試用的 SQLite 改用 `strftime`）
- 資料由 `AttendanceSeeder` 依執行當天往前產生：每名精靈在「昨天往前 14 天」的平日各一筆（不含今天，因為還沒下班），上下班時間用「編號 + 日期」的雜湊（crc32）決定，同一天的結果相同，約 18% 工時不足 8 小時；「可能失蹤」的精靈在最後出勤日之後沒有紀錄（距今天數：E010 7、E012 4、E015 10、E018 8）。因為日期隨執行當天移動，docker 每次啟動都會重新產生（先清空再灌入；沒有任何功能會寫入出勤紀錄，不會弄丟資料）；應用程式時區為 UTC，日期以此為準
- 測試：tests/Feature/AttendanceTest.php

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
