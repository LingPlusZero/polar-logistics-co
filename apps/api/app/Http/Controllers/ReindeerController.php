<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReindeerListRequest;
use App\Http\Requests\ReindeerRequest;
use App\Http\Resources\LeaveResource;
use App\Http\Resources\ReindeerResource;
use App\Models\Elf;
use App\Models\Reindeer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReindeerController extends Controller
{
    // 目前只有 9 隻，不分頁、不搜尋；排序在後端
    public function index(ReindeerListRequest $request)
    {
        $direction = $request->validated('order') ?? 'asc';

        $query = Reindeer::with('caretaker');

        // 年資越高＝到職日越早，所以年資的方向與到職日相反
        if (($request->validated('sort') ?? 'number') === 'seniority') {
            $query->orderBy('hired_at', $direction === 'asc' ? 'desc' : 'asc')->orderBy('number');
        } else {
            $query->orderBy('number', $direction);
        }

        return ReindeerResource::collection($query->get());
    }

    public function store(ReindeerRequest $request)
    {
        // 同時新增時兩邊可能算出同一個編號，放進交易並鎖住讀取，讓後到的等前一筆寫入後再算
        $reindeer = DB::transaction(function () use ($request) {
            Reindeer::query()->lockForUpdate()->count();

            $reindeer = new Reindeer($request->reindeerData());
            $reindeer->number = Reindeer::nextNumber();
            $reindeer->save();

            return $reindeer;
        });

        return (new ReindeerResource($reindeer->load('caretaker')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(ReindeerRequest $request, Reindeer $reindeer)
    {
        $reindeer->update($request->reindeerData());

        return new ReindeerResource($reindeer->load('caretaker'));
    }

    // 該馴鹿的假單也一併刪除
    public function destroy(Reindeer $reindeer)
    {
        $reindeer->delete();

        return response()->noContent();
    }

    // 新增／修改時可選的照護專員：馴鹿管理部的精靈（名冊只有人力資源部能看，所以另開端點）
    public function caretakers()
    {
        return Elf::whereHas('department', fn ($q) => $q->where('name', Reindeer::CARETAKER_DEPARTMENT))
            ->orderBy('number')
            ->get(['id', 'number', 'name']);
    }

    // 登入者擔任照護專員的馴鹿：請假申請可選擇替誰請假
    public function mine(Request $request)
    {
        return Reindeer::where('caretaker_id', $request->user()->id)
            ->orderBy('number')
            ->get(['id', 'number', 'name']);
    }

    // 該馴鹿的請假紀錄（申請日新到舊）
    public function leaves(Reindeer $reindeer)
    {
        $leaves = $reindeer->leaveRequests()
            ->with(['elf', 'reindeer', 'reviewer'])
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->get();

        return LeaveResource::collection($leaves);
    }
}
