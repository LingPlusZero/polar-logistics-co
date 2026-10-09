# 資料表設計

## department
- id、name（unique）、duty（工作內容，可 null）、timestamps
- 共 8 個部門：禮物包裝部、外勤機動部、馴鹿管理部、乖寶寶稽核部、客戶體驗部、運輸部、人力資源部，以及董事會（副聖誕老人所屬，無工作內容；docs/brand.md 只列前 7 個）
- migration：`2026_10_08_000004_create_department_table.php`
- seeder：`DepartmentSeeder`（資料來自 docs/brand.md，另加董事會；以 name 做 updateOrCreate）

## elves
- id、number（精靈編號，unique，登入帳號，例如 E001）、name、department_id（FK → department.id，restrict on delete）、rank（職級：實習精靈／正式精靈／資深精靈／部長／副聖誕老人，PHP 端用 `ElfRank` enum）、hired_at（到職日，年資由此計算，不存欄位）、status（正常／請假／可能失蹤，預設正常，PHP 端用 `ElfStatus` enum）、note（可 null）、api_token（登入權杖的 sha256 雜湊，可 null＝未登入，unique，序列化時隱藏）、timestamps
- password：bcrypt 雜湊（model cast `hashed`，序列化時隱藏）；`ElfSeeder` 只在新增精靈時設定預設測試密碼 `1qaz@WSX3edc`，重複執行不會蓋掉之後修改的密碼；正式環境上線前必須改掉
- migration：`2026_10_09_000001_create_elves_table.php`
- seeder：`ElfSeeder`（初始狀態只在新增時設定，E010、E012、E015、E018 為「可能失蹤」，其餘正常，讓 Demo 有內容；22 名：副聖誕老人 1、各部門部長 7、資深／正式精靈 11、實習精靈 3；以 number 做 updateOrCreate，須排在 DepartmentSeeder 之後）
- 編號對照：E001 副聖誕老人（董事會）、E002–E008 各部門部長（依部門 id 順序）、E009–E019 資深／正式精靈、E020–E022 實習精靈

## reindeer

## career
- id、title、department_id（FK → department.id，可 null＝不限部門，restrict on delete）、description（text）、requirements（text）、benefits（text，可 null）、promotion（text，可 null）、note（text，可 null）、timestamps
- migration：`2026_10_08_000001_create_career_table.php`、`2026_10_08_000003_add_benefits_and_promotion_to_career_table.php`、`2026_10_08_000005_change_career_department_to_foreign_key.php`（把原本的部門名稱文字回填成關聯後移除舊欄位，「各部門」維持 null）
- seeder：`CareerSeeder`（內容來自 docs/website.md「職缺參考」；表內已有資料就不灌入）

## annual_statics
- id、year（unsigned smallint，unique）、gifts_delivered（unsigned bigint）、growth_rate、on_time_rate、complete_rate、feedback_rate（皆 decimal，見 migration）、note（string，可 null）、timestamps
- migration：`2026_10_08_000002_create_annual_statics_table.php`
- seeder：`AnnualStaticSeeder`（docker 啟動時自動執行，以 updateOrCreate 保持重複執行安全）

## 資料表與欄位註解
- 所有資料表與欄位都有 MySQL 註解（`COMMENT`），用資料庫工具或 `SHOW FULL COLUMNS FROM <table>` 可直接看到
- 新建資料表：在 migration 用 `$table->comment()`（表）與欄位的 `->comment()`，見 `create_elves_table`
- 既有資料表（department、career、annual_statics）：由 `2026_10_09_000002_add_comments_to_tables.php` 補上；`change()` 需重述完整欄位定義，之後改欄位型別或 nullable 時要同步更新這支 migration 的對應行
- 新增資料表或欄位時一律附註解；註解內容與本文件的欄位說明保持一致
- 用 mysql CLI 看中文註解需加 `--default-character-set=utf8mb4`，否則會顯示成問號
