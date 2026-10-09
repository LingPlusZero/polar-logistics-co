<?php

namespace App\Enums;

// 審核結果來源：docs/admin.md「精靈請假紀錄」
enum LeaveStatus: string
{
    case Pending = '審核中';
    case Approved = '核准';
    case Rejected = '駁回';
}
