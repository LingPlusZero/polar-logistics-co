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
- 權限：career 的寫入（POST/PUT/DELETE）目前**沒有驗證**，等精靈管理系統登入機制完成後必須補上（`CareerRequest::authorize`）

## 清單
- JSON 欄位一律 camelCase，成功回傳不包 `data`；驗證失敗回 422，找不到回 404

### api/ping 健康檢查
- GET `/api/ping` → `{"status":"ok"}`（測試：tests/Feature/PingTest.php）

### api/career/ 職缺列表
- 欄位：`id`、`title`（必填，≤100）、`departmentId`（選填，須為既有部門 id，空值＝不限部門）、`department`（唯讀，部門名稱，不限部門時為 null）、`description`（必填，≤5000）、`requirements`（必填，≤5000）、`benefits` 福利（選填，≤2000）、`promotion` 轉正機會（選填，≤5000）、`note` 備註（選填，≤2000）
- `description`、`requirements`、`promotion` 為條列欄位：一行一項，以換行字元（`\n`）分隔，前端轉成清單
- 影響欄位：職缺名稱、職缺部門、工作內容、任職資格、福利、轉正機會、備註
- GET `/api/career` 職缺清單 → 200，陣列，依 id 排序
- POST `/api/career` 新增職缺 → 201，回傳該筆
- PUT `/api/career/{id}` 編輯職缺 → 200，回傳該筆（需帶完整欄位）
- DELETE `/api/career/{id}` 刪除職缺 → 204
- 寫入時只送 `departmentId`，不存在的部門回 422
- 測試：tests/Feature/CareerTest.php、CareerSeederTest.php

### api/department 部門
- 欄位：`id`、`name`、`duty`（工作內容，可為 null）
- GET `/api/department` 部門清單 → 200，陣列，依 id 排序（目前唯讀）
- 資料由 `DepartmentSeeder` 依 docs/brand.md 建立
- 測試：tests/Feature/DepartmentTest.php

### api/statics/annual 年度統計
- 欄位：`year`、`giftsDelivered`（份）、`growthRate`（%）、`onTimeRate`（%）、`completeRate`（%）、`feedbackRate`（%）、`note`（可為 null）
- GET `/api/statics/annual?limit=10` 年度統計 → 200，取最新 `limit` 年（預設 10，1–50），由舊到新排序
- 資料由 `AnnualStaticSeeder` 依 docs/website.md 的產生規則建立（固定亂數種子，每次結果相同；2025 年固定為首頁數據列的數字）
- 測試：tests/Feature/AnnualStaticTest.php
