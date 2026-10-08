# api

## 規範
- api 要有測試
- 檢查參數和請求來源，避免 XSS、injection、CSRF 等資安風險
- RESTful API
- 實作後補上 api url 和 規格

## 清單
### api/ping 健康檢查
- GET `/api/ping` → `{"status":"ok"}`（已實作，測試：tests/Feature/PingTest.php）
### api/career/ 職缺列表
- 影響欄位：職缺名稱、職缺部門、工作內容、任職資格、備註
- GET 職缺清單
- POST 新增職缺清單
- PUT 編輯職缺清單
- DELETE 刪除職缺清單
### api/statics/annual 年度統計
- 影響欄位：職缺名稱、職缺部門、工作內容、任職資格、備註
- GET 年度統計