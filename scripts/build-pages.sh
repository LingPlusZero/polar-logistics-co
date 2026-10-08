#!/bin/sh
# 建置 GitHub Pages 用的靜態 Demo：所有前端放進 _site，以子路徑區分
# 用法：PAGES_BASE=/倉庫名稱 sh scripts/build-pages.sh
set -e

BASE="${PAGES_BASE:-/polar-logistics-co}"

rm -rf _site
mkdir -p _site

# build <app> <子路徑>：子路徑空字串代表站台根目錄
build() {
  app="$1"
  subpath="$2"

  (
    cd "apps/$app"
    npm ci
    # 環境變數優先於 .env.demo，倉庫改名時不用改檔案
    VITE_BASE="$BASE$subpath/" VITE_ADMIN_URL="$BASE/admin/" npm run build:demo
  )

  mkdir -p "_site$subpath"
  cp -r "apps/$app/dist/." "_site$subpath/"
}

build website ""

# 精靈管理系統完成後取消註解，並同步開啟官網頁尾「員工專區」（SiteFooter.vue 的 IS_ADMIN_ENABLED）
# build admin "/admin"
