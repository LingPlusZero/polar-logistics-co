<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnnualStaticResource;
use App\Models\AnnualStatic;
use Illuminate\Http\Request;

class AnnualStaticController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        // 取最新 N 年（預設 10 年），再依年份由舊到新排序，方便直接畫折線圖
        $rows = AnnualStatic::orderByDesc('year')
            ->limit($validated['limit'] ?? 10)
            ->get()
            ->sortBy('year')
            ->values();

        return AnnualStaticResource::collection($rows);
    }
}
