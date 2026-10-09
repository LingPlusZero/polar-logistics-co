<?php

namespace App\Enums;

// 狀態來源：docs/admin.md「精靈名冊」
enum ElfStatus: string
{
    case Normal = '正常';
    case OnLeave = '請假';
    case MaybeMissing = '可能失蹤';
}
