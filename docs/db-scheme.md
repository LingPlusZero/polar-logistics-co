# 資料表設計

## department
- id、name（unique）、duty（工作內容，可 null）、timestamps
- 共 7 個部門：禮物包裝部、外勤機動部、馴鹿管理部、乖寶寶稽核部、客戶體驗部、運輸部、人力資源部
- migration：`2026_10_08_000004_create_department_table.php`
- seeder：`DepartmentSeeder`（資料來自 docs/brand.md，以 name 做 updateOrCreate）

## elves

## reindeer

## career
- id、title、department_id（FK → department.id，可 null＝不限部門，restrict on delete）、description（text）、requirements（text）、benefits（text，可 null）、promotion（text，可 null）、note（text，可 null）、timestamps
- migration：`2026_10_08_000001_create_career_table.php`、`2026_10_08_000003_add_benefits_and_promotion_to_career_table.php`、`2026_10_08_000005_change_career_department_to_foreign_key.php`（把原本的部門名稱文字回填成關聯後移除舊欄位，「各部門」維持 null）
- seeder：`CareerSeeder`（內容來自 docs/website.md「職缺參考」；表內已有資料就不灌入）

## annual_statics
- id、year（unsigned smallint，unique）、gifts_delivered（unsigned bigint）、growth_rate、on_time_rate、complete_rate、feedback_rate（皆 decimal，見 migration）、note（string，可 null）、timestamps
- migration：`2026_10_08_000002_create_annual_statics_table.php`
- seeder：`AnnualStaticSeeder`（docker 啟動時自動執行，以 updateOrCreate 保持重複執行安全）
