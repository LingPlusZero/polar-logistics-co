<?php

namespace App\Enums;

// 狀態來源：docs/admin.md「精靈被申訴紀錄」
enum ComplaintStatus: string
{
    case Processing = '處理中';
    case Closed = '已結案';
}
