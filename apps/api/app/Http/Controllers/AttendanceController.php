<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceListRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Elf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

// 沒有真正的打卡機制，所以只提供列表
class AttendanceController extends Controller
{
    public function index(AttendanceListRequest $request)
    {
        $query = Attendance::with('elf');

        $search = $request->validated('search');
        $departmentId = $request->validated('departmentId');

        if ($search || $departmentId) {
            $query->whereHas('elf', function ($elf) use ($search, $departmentId) {
                if ($search) {
                    // 搜尋字串中的 % _ 要跳脫，避免使用者輸入變成萬用字元
                    $like = '%'.addcslashes($search, '\\%_').'%';
                    $elf->where(fn ($q) => $q->where('number', 'like', $like)->orWhere('name', 'like', $like));
                }

                if ($departmentId) {
                    $elf->where('department_id', $departmentId);
                }
            });
        }

        // 以上班時間的範圍比對，不對欄位套函式，才用得到 clock_in 索引
        if ($from = $request->validated('dateFrom')) {
            $query->where('clock_in', '>=', CarbonImmutable::parse($from)->startOfDay());
        }

        if ($to = $request->validated('dateTo')) {
            $query->where('clock_in', '<', CarbonImmutable::parse($to)->addDay()->startOfDay());
        }

        $sort = $request->validated('sort') ?? 'clockIn';
        $direction = $request->validated('order') ?? 'desc';

        match ($sort) {
            // 用子查詢取精靈編號排序，不必 join
            'number' => $query->orderBy(
                Elf::select('number')->whereColumn('elves.id', 'attendance.elf_id'),
                $direction,
            ),
            'workMinutes' => $query->orderByRaw($this->workSecondsSql().' '.($direction === 'asc' ? 'asc' : 'desc')),
            'clockOut' => $query->orderBy('clock_out', $direction),
            default => $query->orderBy('clock_in', $direction),
        };

        // 同分時固定以上班時間新到舊、id 排序，分頁順序才穩定
        if ($sort !== 'clockIn') {
            $query->orderByDesc('clock_in');
        }
        $query->orderByDesc('id');

        $page = $query->paginate(
            $request->validated('perPage') ?? AttendanceListRequest::DEFAULT_PER_PAGE,
            ['*'],
            'page',
            $request->validated('page') ?? 1,
        );

        return response()->json([
            'items' => AttendanceResource::collection($page->items())->resolve(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'perPage' => $page->perPage(),
            'lastPage' => $page->lastPage(),
        ]);
    }

    // 工作時數（秒）的 SQL 運算式；MySQL 與測試用的 SQLite 語法不同
    private function workSecondsSql(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "(strftime('%s', clock_out) - strftime('%s', clock_in))"
            : 'TIMESTAMPDIFF(SECOND, clock_in, clock_out)';
    }
}
