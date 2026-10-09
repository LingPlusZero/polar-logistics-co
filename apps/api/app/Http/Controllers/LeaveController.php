<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Http\Requests\LeaveApplyRequest;
use App\Http\Requests\LeaveListRequest;
use App\Http\Requests\LeaveRecordListRequest;
use App\Http\Requests\LeaveRejectRequest;
use App\Http\Resources\LeaveResource;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LeaveController extends Controller
{
    // 自己的請假單
    public function mine(LeaveListRequest $request)
    {
        return $this->paginated(LeaveRequest::where('elf_id', $request->user()->id), $request);
    }

    // 審核範圍內的假單（範圍見 LeaveRequest::scopeReviewableBy）
    public function review(LeaveListRequest $request)
    {
        return $this->paginated(LeaveRequest::reviewableBy($request->user()), $request);
    }

    // 人力資源部看全部精靈的請假紀錄；搜尋與部門針對申請人
    public function records(LeaveRecordListRequest $request)
    {
        // 馴鹿的假單（照護專員代請）不是精靈自己的請假，不列入精靈請假紀錄
        $query = LeaveRequest::whereNull('reindeer_id');

        $search = $request->validated('search');
        $departmentId = $request->validated('departmentId');

        if ($search || $departmentId) {
            $query->whereHas('elf', function (Builder $elf) use ($search, $departmentId) {
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

        return $this->paginated($query, $request);
    }

    public function store(LeaveApplyRequest $request)
    {
        [$start, $end] = $request->period();
        $elf = $request->user();

        $leave = LeaveRequest::create([
            'elf_id' => $elf->id,
            'reindeer_id' => $request->validated('reindeerId'),
            'leave_type' => $request->validated('leaveType'),
            'start_date' => $start,
            'end_date' => $end,
            'applied_at' => now()->toDateString(),
            'status' => LeaveStatus::Pending,
        ]);

        return (new LeaveResource($leave->load(['elf', 'reindeer', 'reviewer'])))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        return $this->decide($request, $leave, LeaveStatus::Approved);
    }

    public function reject(LeaveRejectRequest $request, LeaveRequest $leave)
    {
        return $this->decide($request, $leave, LeaveStatus::Rejected, $request->validated('reason'));
    }

    // 審核：只能審核範圍內、且尚在審核中的假單
    private function decide(Request $request, LeaveRequest $leave, LeaveStatus $status, ?string $reason = null)
    {
        $reviewer = $request->user();

        if (! LeaveRequest::reviewableBy($reviewer)->whereKey($leave->id)->exists()) {
            return response()->json(['message' => '沒有權限審核這張假單'], Response::HTTP_FORBIDDEN);
        }

        // 條件式更新，兩人同時審核同一張時只有先到的成功
        $updated = LeaveRequest::whereKey($leave->id)
            ->where('status', LeaveStatus::Pending->value)
            ->update([
                'status' => $status->value,
                'reviewed_at' => now()->toDateString(),
                'reviewer_id' => $reviewer->id,
                'reject_reason' => $reason,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['message' => '這張假單已經審核過'], Response::HTTP_CONFLICT);
        }

        return new LeaveResource($leave->refresh()->load(['elf', 'reindeer', 'reviewer']));
    }

    private function paginated(Builder $query, LeaveListRequest $request)
    {
        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        // 申請日期起迄含當天；迄日用「小於隔天」比對，不論欄位是否帶時間都正確
        if ($from = $request->validated('dateFrom')) {
            $query->where('applied_at', '>=', $from);
        }

        if ($to = $request->validated('dateTo')) {
            $query->where('applied_at', '<', CarbonImmutable::parse($to)->addDay()->toDateString());
        }

        // 申請日新到舊；同一天以 id 排序讓分頁順序穩定
        $page = $query->with(['elf', 'reindeer', 'reviewer'])->orderByDesc('applied_at')->orderByDesc('id')->paginate(
            $request->validated('perPage') ?? LeaveListRequest::DEFAULT_PER_PAGE,
            ['*'],
            'page',
            $request->validated('page') ?? 1,
        );

        return response()->json([
            'items' => LeaveResource::collection($page->items())->resolve(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'perPage' => $page->perPage(),
            'lastPage' => $page->lastPage(),
        ]);
    }
}
