<?php

namespace App\Enums;

// 職級來源：docs/brand.md「部門與職級」
enum ElfRank: string
{
    case Intern = '實習精靈';
    case Regular = '正式精靈';
    case Senior = '資深精靈';
    case Minister = '部長';
    case ViceSanta = '副聖誕老人';

    // 有權審核假單的職級（部長審部門、副聖誕老人審部長）
    public function canReviewLeave(): bool
    {
        return $this === self::Minister || $this === self::ViceSanta;
    }
}
