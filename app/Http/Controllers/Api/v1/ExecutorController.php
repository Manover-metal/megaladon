<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Executor\ExecutorUpdateRequest;
use App\Http\Requests\Executor\FavoriteExecutorRequest;
use App\Services\v1\ExecutorService;
use Illuminate\Http\Request;

class ExecutorController extends ApiController
{
    private ExecutorService $executorService;

    public function __construct() {
        $this->executorService = new ExecutorService();
    }

    public function update(ExecutorUpdateRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->executorService->update($data));
    }

    public function indexMy()
    {
        return $this->result($this->executorService->indexMy());
    }

    public function addToFavorites(FavoriteExecutorRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->executorService->addToFavorites($data));
    }

    public function myRatings()
    {
        return $this->result($this->executorService->myRatings());
    }

    public function info(int $id)
    {
        return $this->result($this->executorService->info($id));
    }
}
