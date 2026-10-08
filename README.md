# 極地物流股份有限公司 Polar Logistics Co., Ltd.

虛構的聖誕老人物流公司，官網與精靈管理系統。

## Demo
部署到 GitHub Pages 的靜態 Demo，資料僅存在瀏覽器。

- 官網：https://lingpluszero.github.io/polar-logistics-co/
- 精靈管理系統：（建置中）

首次啟用需到倉庫 Settings → Pages，Source 選「GitHub Actions」；之後 push 到 `main` 會自動部署，細節見 `docs/demo-strategy.md`。

## 截圖
（待補）

## 如何本機用 Docker 跑完整版
```sh
docker compose up -d          # 啟動全部服務，並自動 migrate
```

| 服務 | 網址 |
|---|---|
| 官網 | http://localhost:5173 |
| 精靈管理系統 | http://localhost:5174 |
| API（nginx） | http://localhost:8080/api |
| MySQL | localhost:33060（polar / polar） |

- 前端開發環境預設為 Demo 模式（`.env.development`），要接真實 API 時把 `VITE_DEMO_MODE` 改成 `false`。
- 首次啟動需安裝 composer 與 npm 套件，請稍等。
