# 官網實作細節

文案與設定以 `docs/website.md` 為準，此文件只記技術做法。

## 版面
- `SiteHeader`：fixed、小於 48rem 為漢堡選單、換頁與按 Esc 會收起
- `SiteFooter`：員工專區網址來自 `VITE_ADMIN_URL`（見各 `.env`）
- Demo 橫幅 fixed，會設定 CSS 變數 `--banner-height`，讓頁首下移
- 路由標題由 router `meta.title` 設定；Demo 模式用 hash history，避免 GitHub Pages 重新整理 404
- RWD 斷點統一由小到大：`48rem`、`64rem`

## 首頁
- `CountUp`：IntersectionObserver，第一次進入畫面才跳動，之後停止觀察；支援 `prefers-reduced-motion`
- `DepartmentCarousel`：自動輪播（6 秒），hover 與 focus 時暫停；各部門背景色與 icon 在 `data/departments.ts`、`DepartmentIcon.vue`
- 首頁區塊順序：Banner、數據列、關於我們、五大業務；底色白／淺色交錯
- 標題區背景：`HeroBackdrop`（SVG，同心緯線加放射經線，圓心靠右，對應總部北緯 90 度；首頁 banner 另加品牌符號浮水印，內頁不加以免壓到標題）
- 內頁標題區統一用 `PageHero`（小標籤、標題、副標、底部細紅線）；首頁 banner 按鈕下方有關鍵資訊列
- `DeadlineCountdown`：每秒更新；期限為本地時間 12/25 00:00，已過則改算隔年；數字用 tabular-nums 避免抖動
- 關於我們：寬螢幕左右兩欄（左：公司沿革時間軸，右：願景使命的三張價值卡，各有線條 icon），窄螢幕直向排列；文案在 `data/about.ts`，icon 在 `ValueIcon.vue`
- 數據列、五大業務文案寫在前端常數，來源 `docs/website.md`

## 投資人關係
- `EChart`：echarts 6，只註冊 LineChart、Grid、Legend、Tooltip、CanvasRenderer；ResizeObserver 偵測寬度後重新產生設定
- 五個指標合併一張折線圖：左軸禮物數（億份）、右軸一為成長率、右軸二為三個送達率（固定 99–100%，避免微幅波動被放大）
- 窄螢幕（< 640px）隱藏第三條軸，數值看 tooltip
- 圖表下方保留資料表，兼顧可讀性與無障礙
- 區塊順序：標題區、董事長致詞、營運表現、北風計畫、愛心同行；底色白／淺色交錯
- 董事長致詞：文案在 `data/chairman.ts`，稱謂後第一段字級放大，署名靠右
- 資料：`api.statics.annual()`（最新 10 年，由舊到新）
- 因為 echarts 體積大，只有此頁使用，隨路由 lazy load

## 人才招募
- 資料：`api.career.list()`
- `description`、`requirements`、`promotion` 在資料庫以換行分隔，前端拆成清單；`benefits`、`note` 為單行文字
- `department` 為 null 時顯示「各部門」（對應 docs 職缺 8）
- 沒有職缺資料時不顯示列表，載入失敗顯示「資料載入失敗」

## 聯繫我們
- 純前端表單，送出不發任何請求；兩欄版面（左聯絡資訊、右表單卡片），icon 在 `ContactIcon.vue`
- 送出後以前端隨機產生案件編號（PLC-2025-六碼）與佇列順位，顯示在回覆下方

## 文案來源備註
- 「北風計畫」補充註腳、「愛心同行」內文、「關於我們」（公司沿革、願景使命、董事長致詞）由 Claude 擬寫，已寫入 `docs/website.md`（使用者授權）
