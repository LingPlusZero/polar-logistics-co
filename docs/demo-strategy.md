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
