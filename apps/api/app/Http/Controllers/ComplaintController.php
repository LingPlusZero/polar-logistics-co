<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Http\Requests\ComplaintCloseRequest;
use App\Http\Requests\ComplaintListRequest;
use App\Http\Requests\ComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Models\Elf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class ComplaintController extends Controller
{
    public function index(ComplaintListRequest $request)
    {
        $query = Complaint::with(['elf', 'handler']);

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        $search = $request->validated('search');
        $departmentId = $request->validated('departmentId');

        // 搜尋與部門都針對被申訴人
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

        // 起迄含當天；迄日用「小於隔天」比對，不論欄位是否帶時間都正確
        if ($from = $request->validated('dateFrom')) {
            $query->where('filed_at', '>=', $from);
        }

        if ($to = $request->validated('dateTo')) {
            $query->where('filed_at', '<', CarbonImmutable::parse($to)->addDay()->toDateString());
        }

        // 新的在前；同一天以 id 排序讓分頁順序穩定
        $page = $query->orderByDesc('filed_at')->orderByDesc('id')->paginate(
            $request->validated('perPage') ?? ComplaintListRequest::DEFAULT_PER_PAGE,
            ['*'],
            'page',
            $request->validated('page') ?? 1,
        );

        return response()->json([
            'items' => ComplaintResource::collection($page->items())->resolve(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'perPage' => $page->perPage(),
            'lastPage' => $page->lastPage(),
        ]);
    }

    // 申訴人不是人力資源部，所以只回傳確認用的最少資訊，不回傳整筆紀錄
    public function store(ComplaintRequest $request)
    {
        $target = Elf::where('number', $request->validated('elfNumber'))->firstOrFail();

        $complaint = Complaint::create([
            'elf_id' => $target->id,
            'complainant_id' => $request->user()->id,
            'reason' => $request->validated('reason'),
            'filed_at' => now()->toDateString(),
            'status' => ComplaintStatus::Processing,
        ]);

        return response()->json([
            'id' => $complaint->id,
            'elfNumber' => $target->number,
            'elfName' => $target->name,
        ], Response::HTTP_CREATED);
    }

    // 已結案不能再更動；處理人由登入者自動帶入
    public function close(ComplaintCloseRequest $request, Complaint $complaint)
    {
        if ($complaint->status === ComplaintStatus::Closed) {
            return response()->json(['message' => '這筆申訴已經結案'], Response::HTTP_CONFLICT);
        }

        $complaint->update([
            'status' => ComplaintStatus::Closed,
            'resolution' => $request->validated('resolution'),
            'handler_id' => $request->user()->id,
        ]);

        return new ComplaintResource($complaint->load(['elf', 'handler']));
    }
}
