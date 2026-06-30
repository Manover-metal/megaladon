<?php

namespace App\Repositories;

use App\Models\Executor;

class ExecutorRepo 
{
    public function store(array $data) : Executor
    {
        return Executor::create($data);
    }

    public function findByUserId(int $user_id)
    {
        return Executor::where('user_id', $user_id)
            ->first();
    }

    public function info(int $user_id)
    {
        return Executor::with(['services', 'city'])
            ->where('user_id', $user_id)
            ->first();
    }

    public function update(int $user_id, array $data)
    {
        return Executor::where('user_id', $user_id)
            ->update($data);
    }
}