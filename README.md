# 極地物流股份有限公司 Polar Logistics Co., Ltd.

虛構的聖誕老人物流公司，官網與精靈管理系統。

## Demo
部署到 GitHub Pages 的靜態 Demo，資料僅存在瀏覽器。

- 官網：https://lingpluszero.github.io/polar-logistics-co/
- 精靈管理系統：https://lingpluszero.github.io/polar-logistics-co/admin/（Demo 帳號 E001–E022，密碼輸入自己的帳號；任何操作只存在你的瀏覽器）

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
| 精靈管理系統 | https://localhost:5174（自簽憑證，瀏覽器會警告） |
| API（nginx） | https://localhost:8443/api（自簽憑證，瀏覽器會警告；8080 只轉址到 HTTPS） |
| MySQL | localhost:33060（polar / polar） |

- 前端開發環境的模式由各 app 的 `.env.development` 決定，`VITE_DEMO_MODE` 改 `true`／`false` 即可切換。
- 首次開啟 HTTPS 網址，瀏覽器會警告自簽憑證，選「繼續前往」即可。
- 首次啟動需安裝 composer 與 npm 套件，請稍等。
