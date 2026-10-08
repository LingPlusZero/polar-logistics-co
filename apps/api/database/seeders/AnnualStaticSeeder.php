<?php

namespace Database\Seeders;

use App\Models\AnnualStatic;
use Illuminate\Database\Seeder;

// 規則見 docs/website.md「營運表現突破與成長動能」
class AnnualStaticSeeder extends Seeder
{
    private const LATEST_YEAR = 2025;

    private const YEARS = 10;

    public function run(): void
    {
        // 固定亂數種子：每次產出的數字都一樣，才不會每次 migrate 後曲線不同
        mt_srand(1224);

        for ($year = self::LATEST_YEAR - self::YEARS + 1; $year <= self::LATEST_YEAR; $year++) {
            AnnualStatic::updateOrCreate(['year' => $year], $this->row($year));
        }
    }

    private function row(int $year): array
    {
        $onTime = $this->between(99.91, 99.98, 2);
        $row = [
            // 20 億份上下浮動（-9% ~ +7%），取到十萬位
            'gifts_delivered' => (int) round(2_000_000_000 * (1 + $this->between(-0.09, 0.07, 3)), -5),
            'growth_rate' => $this->between(3, 7, 1),
            'on_time_rate' => $onTime,
            // 完整送達率略低於準時率
            'complete_rate' => round($onTime - $this->between(0.02, 0.05, 2), 2),
            'feedback_rate' => 100,
            // 約四分之一的年度備註「持續優化中」，不解釋優化什麼
            'note' => mt_rand(1, 4) === 1 ? '持續優化中' : null,
        ];

        // 最新一年需與官網首頁數據列一致（22.3 億份、+6.7%、99.97%）
        if ($year === self::LATEST_YEAR) {
            $row['gifts_delivered'] = 2_230_000_000;
            $row['growth_rate'] = 6.7;
            $row['on_time_rate'] = 99.97;
            $row['complete_rate'] = 99.95;
        }

        return $row;
    }

    private function between(float $min, float $max, int $decimals): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
    }
}
