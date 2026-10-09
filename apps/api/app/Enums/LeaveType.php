<?php

namespace App\Enums;

// 假別與天數來源：docs/admin.md「請假申請/審核」
enum LeaveType: string
{
    case Sick = '普通病假';
    case MagicDepletion = '魔力枯竭假';
    case HumanSightingTrauma = '被人類目擊後心理創傷假';

    // 每種假別固定的天數（含請假當天，連續日曆天）
    public function days(): int
    {
        return match ($this) {
            self::Sick => 1,
            self::MagicDepletion => 2,
            self::HumanSightingTrauma => 7,
        };
    }
}
