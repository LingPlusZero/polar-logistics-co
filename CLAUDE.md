# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

使用繁體中文

## 專案概覽

「極地物流股份有限公司」是虛構的聖誕老人物流公司，官網與內部系統都要做得**像真的企業系統**：語氣正經、設計正經，荒謬感只藏在設定、數字與小字註腳裡。

## 技術棧
- 後端：Laravel 13
- 前端：Vue 3 + Vue Router + Vite + TypeScript
- 伺服器：nginx:1.27-alpine
- 資料庫：MySQL 8.4
- 環境：Docker

## 架構說明
polar-logistics-co/
├── apps/
│   ├── api/                   # Laravel 13 純 API
│   ├── website/               # 官網
│   └── admin/                 # 內部系統
├── shared/                    # 品牌色/字體 (styles/)、共用元件 (components/)、api 抽象層 (api/)
├── docker/                    # php、nginx 設定
├── docker-compose.yml
├── CLAUDE.md
└── README.md                  # 放各 demo 連結與截圖

## 資料來源
- 官網與所有內部系統的資料都走 Laravel API
- 只有部署到 GitHub Pages 的靜態 Demo 建置改讀 JSON 快照
- DEMO 詳情見 `docs/demo-strategy.md`

## docker
PHP、composer 等服務全部在容器內執行
啟動docker後會一起啟用所有服務、migrate資料

## apps/api
- Laravel 移除純API不會用到的檔案
- 前端 vite.config 設定 proxy，讓前端連得上 api/

## 常用指令
```sh
docker compose up -d                              # 全部服務（含自動 migrate）
docker compose up website                         # 只跑官網
docker compose up admin                           # 只跑精靈管理系統
docker compose up -d --build                      # 修改 Dockerfile 後重建
docker compose down                               # 停止全部服務
docker compose down -v                            # 停止並清除資料庫與套件 volume（重置）
docker compose logs -f php                        # 看某服務的 log

# api（在 php 容器內執行）
docker compose exec php php artisan test          # 跑測試
docker compose exec php php artisan migrate       # 執行 migrate
docker compose exec php php artisan migrate:fresh --seed  # 重建資料表並塞資料
docker compose exec php php artisan make:model Career -mf # 建 model、migration、factory
docker compose exec php composer require <套件>   # 安裝 php 套件

# 前端（在對應容器內執行，website 可換成 admin）
docker compose exec website npm install <套件>    # 安裝前端套件
docker compose exec website npm run build         # 型別檢查並建置
docker compose exec website npm run build:demo    # GitHub Pages 的 Demo 建置
```

## 開發流程
1. 完整專案骨架
2. 官網
3. 精靈管理系統

## 規範
- api 與 view 不要混在一起
- 遵守 coding-style (見`docs/coding-style.md`)
- 各前端會共用品牌色、字體、部分元件與 api 抽象層，抽出來放進 shared/
- 機敏資訊不要用明碼傳輸

## 重要文件連結

以下是所有文案、數據與設定的唯一來源，文案照 docs 原文，不要自行改寫或補梗：
- 視覺設計規範、品牌介紹：`docs/brand.md`
- 官網設定：`docs/website.md`
- 精靈管理系統設定：`docs/admin.md`

以下是技術相關說明，會在開發過程中做更動，請隨時保持同步：
- DEMO 說明：`docs/demo-strategy.md`
- db scheme：`docs/db-scheme.md`
- api 清單：`docs/api.md`
- 官網實作細節：`docs/website-implementation.md`
- 精靈管理系統實作細節：`docs/admin-implementation.md`
- 架構概要：此文件
- 架構與技術細節：`docs/architecture.md`

## 文件同步事項
- 用清單式寫法
- 補充技術細節，但不需要放完整程式碼，關鍵語法或套件即可
- 可另外新增文本，要更新到重要文件連結
- 遇到不能自行改的文件，內容有缺時，請提醒我補上

## 版控事項
- commit前確認新建功能正常運作，不要自行commit。
### commit 訊息
- 格式為 `type(scope): subject`
- type：`feat`、`fix`、`docs`、`style`、`refactor`、`test`、`chore`、`build`、`ci`
- scope：`website`、`api`、`docker`、`docs`、`repo`（跨多個部分）、`admin`
- subject：用繁體中文、簡短描述做了什麼；需要時空一行再寫內文。
- 範例：`feat(website): 新增首頁數據列跳動動畫`、`fix(docker): 修正 php 容器的 storage 權限`
