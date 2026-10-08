<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareerRequest;
use App\Http\Resources\CareerResource;
use App\Models\Career;
use Illuminate\Http\Response;

class CareerController extends Controller
{
    public function index()
    {
        return CareerResource::collection(Career::with('department')->orderBy('id')->get());
    }

    public function store(CareerRequest $request)
    {
        $career = Career::create($request->careerData());

        return (new CareerResource($career->load('department')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(CareerRequest $request, Career $career)
    {
        $career->update($request->careerData());

        return new CareerResource($career->load('department'));
    }

    public function destroy(Career $career)
    {
        $career->delete();

        return response()->noContent();
    }
}
