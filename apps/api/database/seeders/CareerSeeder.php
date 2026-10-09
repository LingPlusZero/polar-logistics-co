<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\Department;
use Illuminate\Database\Seeder;

// 文案來源：docs/website.md「補充資訊 / 職缺參考」，需與原文一致
// 條列欄位（description、requirements、benefits）以換行分隔，由前端轉成清單
class CareerSeeder extends Seeder
{
    public function run(): void
    {
        // 之後職缺改由精靈管理系統維護，已有資料就不再灌入，避免重新建立被刪除或改名的職缺
        if (Career::exists()) {
            return;
        }

        $departmentIds = Department::pluck('id', 'name');

        foreach ($this->careers() as $career) {
            // 資料以部門名稱撰寫方便對照文件；「各部門」不是實際部門，department_id 維持空值
            $career['department_id'] = $departmentIds[$career['department']] ?? null;
            unset($career['department']);

            Career::create($career);
        }
    }

    private function lines(string ...$items): string
    {
        return implode("\n", $items);
    }

    private function careers(): array
    {
        return [
            [
                'title' => '煙囪突入專員',
                'department' => '外勤機動部',
                'description' => $this->lines(
                    '於 12/24 夜間執行住戶入戶作業，確保禮物妥善放置於指定位置',
                    '評估各類煙囪口徑，必要時啟用「替代入口評估機制」',
                    '完成作業後清除煙灰痕跡，維持「不留痕跡」服務標準',
                ),
                'requirements' => $this->lines(
                    '具 3 年以上狹窄空間作業經驗',
                    '不畏高溫，能適應壁爐餘燼環境',
                    '無幽閉恐懼、無懼高症（面試時會現場確認）',
                    '加分項：曾於無煙囪住宅成功入戶',
                ),
                'benefits' => '專屬高風險職務保險、煙灰清潔津貼',
                'note' => null,
            ],
            [
                'title' => '專職駕駛員（限一名）',
                'department' => '運輸部',
                'description' => $this->lines(
                    '駕駛主力運輸載具 SLEIGH-X 24，於 12/24 夜間完成全球一圈',
                    '與動力單元 01–09 協同作業',
                ),
                'requirements' => $this->lines(
                    '具 200 年以上駕駛經驗',
                    '無任何肇事紀錄',
                    '須配合夜間工作，須能長時間坐姿作業',
                    '須自備紅色制服（公司不補助）',
                ),
                'benefits' => null,
                'note' => '本職缺目前由現任人員續任，歡迎投遞履歷建立人才庫。',
            ],
            [
                'title' => '動力單元照護專員',
                'department' => '馴鹿管理部',
                'description' => $this->lines(
                    '負責 01–09 號動力單元之日常養護、餵食與維護紀錄',
                    '協助夜間照明模組（01 號）之光源調校',
                    '處理動力單元特休申請相關文件',
                ),
                'requirements' => $this->lines(
                    '具大型動物照護經驗',
                    '熟悉燕麥燃料之調配與補給',
                    '能與 07 號單元建立良好關係（已有三任專員離職）',
                ),
                'benefits' => '免費體驗動力單元近距離接觸',
                'note' => null,
            ],
            [
                'title' => '禮物包裝技術員',
                'department' => '禮物包裝部',
                'description' => $this->lines(
                    '於產線上以每分鐘 4,200 份之速度完成禮物包裝',
                    '確保外觀良率達 99.7% 以上',
                ),
                'requirements' => $this->lines(
                    '手指靈活，視力良好',
                    '能承受高度重複作業',
                    '對緞帶有深入理解',
                ),
                'benefits' => null,
                'note' => '良率未達標者將面談，面談地點為包裝部角落的小房間。',
            ],
            [
                'title' => '乖寶寶稽核員',
                'department' => '乖寶寶稽核部',
                'description' => $this->lines(
                    '依內部標準審核全球名單，判定「乖」與「不乖」',
                    '撰寫稽核報告，並出席年度名單爭議會議',
                ),
                'requirements' => $this->lines(
                    '具倫理判斷能力',
                    '能在高度爭議環境下保持中立',
                    '須簽署「不對審核結果發表任何個人意見」同意書',
                ),
                'benefits' => null,
                'note' => '本部門之判定標準屬營業機密，恕不公開。',
            ],
            [
                'title' => '客戶體驗專員',
                'department' => '客戶體驗部',
                'description' => $this->lines(
                    '處理「我沒收到禮物」之客戶來信與來電',
                    '將每一則來信妥善列入處理佇列',
                ),
                'requirements' => $this->lines(
                    '具極高之耐心與同理心',
                    '能在大量客訴下保持微笑',
                    '能接受預估等候時間為「到明年」',
                ),
                'benefits' => '全年無休加班津貼（以餅乾計）',
                'note' => null,
            ],
            [
                'title' => '人力資源排班專員',
                'department' => '人力資源部',
                'description' => $this->lines(
                    '使用本公司「精靈管理系統」進行全年度人力調度',
                    '處理請假申請（含魔力枯竭假、被人類目擊後心理創傷假）',
                    '於聖誕夜前 72 小時處理人力缺口預警',
                ),
                'requirements' => $this->lines(
                    '熟悉排班與勞資規則',
                    '能處理精靈間之宿怨衝突（如甲乙不可同班）',
                    '須具備良好心理素質：名冊內偶有無法解釋之異動，請勿過度追問',
                ),
                'benefits' => null,
                'note' => null,
            ],
            [
                'title' => '實習精靈（全年度招募）',
                'department' => '各部門',
                'description' => '於各部門輪調學習，了解聖誕夜作業流程',
                'requirements' => $this->lines(
                    '年滿 50 歲（精靈年齡）',
                    '對本公司使命有高度熱忱',
                ),
                // 轉正機會算福利的一部分（docs/website.md「轉正機會」）
                'benefits' => $this->lines(
                    '實習期滿 12 個月，經部門主管評核後，可轉任正式精靈',
                    '評核項目：出勤穩定度、部門作業表現、對公司使命之熱忱、聖誕夜當晚表現',
                    '歷年實習精靈轉正率：87%（以實際完成實習者計算）',
                    '轉正後取得正式員工編號、納入森林基金提撥，並正式計入在職人數',
                    '轉正名額依當年度人力需求核定',
                ),
                'note' => '實習期間不計入在職人數統計。',
            ],
        ];
    }
}
