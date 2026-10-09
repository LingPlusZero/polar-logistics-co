<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElfListRequest;
use App\Http\Requests\ElfRequest;
use App\Http\Resources\ElfResource;
use App\Models\Elf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ElfController extends Controller
{
    public function index(ElfListRequest $request)
    {
        $query = Elf::with('department')->withMax('attendances', 'clock_in');

        if ($search = $request->validated('search')) {
            // 搜尋字串中的 % _ 要跳脫，避免使用者輸入變成萬用字元
            $like = '%'.addcslashes($search, '\\%_').'%';
            $query->where(fn ($q) => $q->where('number', 'like', $like)->orWhere('name', 'like', $like));
        }

        if ($departmentId = $request->validated('departmentId')) {
            $query->where('department_id', $departmentId);
        }

        $sort = $request->validated('sort') ?? 'number';
        $direction = $request->validated('order') ?? 'asc';

        // 年資越高＝到職日越早，所以年資的方向與到職日相反
        match ($sort) {
            'department' => $query->orderBy('department_id', $direction),
            'seniority' => $query->orderBy('hired_at', $direction === 'asc' ? 'desc' : 'asc'),
            default => null,
        };
        // 同分時以編號排序讓分頁順序穩定；依編號排序時就是使用者選的方向
        $query->orderBy('number', $sort === 'number' ? $direction : 'asc');

        $page = $query->paginate(
            $request->validated('perPage') ?? ElfListRequest::DEFAULT_PER_PAGE,
            ['*'],
            'page',
            $request->validated('page') ?? 1,
        );

        return response()->json([
            'items' => ElfResource::collection($page->items())->resolve(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'perPage' => $page->perPage(),
            'lastPage' => $page->lastPage(),
        ]);
    }

    public function store(ElfRequest $request)
    {
        // 同時新增時兩邊可能算出同一個編號，放進交易並鎖住讀取，讓後到的等前一筆寫入後再算
        $elf = DB::transaction(function () use ($request) {
            Elf::query()->lockForUpdate()->count();

            $elf = new Elf($request->elfData());
            $elf->number = Elf::nextNumber();
            // 新進精靈先用預設密碼，登入後自行修改
            $elf->password = Elf::DEFAULT_PASSWORD;
            $elf->save();

            return $elf;
        });

        return (new ElfResource($elf->load('department')->loadMax('attendances', 'clock_in')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(ElfRequest $request, Elf $elf)
    {
        $elf->update($request->elfData());

        return new ElfResource($elf->load('department')->loadMax('attendances', 'clock_in'));
    }

    public function destroy(Request $request, Elf $elf)
    {
        // 刪掉自己會讓目前的登入立刻失效，也可能讓人力資源部沒有人能管理名冊
        if ($request->user()->is($elf)) {
            return response()->json(['message' => '不能刪除自己'], 422);
        }

        $elf->delete();

        return response()->noContent();
    }
}
