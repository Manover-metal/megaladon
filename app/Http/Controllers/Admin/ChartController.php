<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advert;
use App\Models\Executor;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;

class ChartController extends Controller
{
    public function index()
    {
        return view('charts', [
            'users' => $this->getYearStats(User::query()),
            'orders' => $this->getYearStats(Order::query()),
            'adverts' => $this->getYearStats(Advert::query()),
            'executors' => $this->getYearStats(Executor::query()),
            'stores' => $this->getYearStats(Store::query()),
            'countUsers' => User::count(),
            'countOrders' => Order::count(),
            'countAdverts' => Advert::count(),
            'countExecutors' => Executor::count(),
            'countStores' => Store::count(),
        ]);
    }

    private function getYearStats($query)
    {
        $rows = $query->select('id', 'created_at')
        ->whereYear('created_at', '=', Carbon::now()->format('Y'))
        ->get()
        ->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('m');
        });

        $rowsmcount = [];
        $rowsArr = [];

        foreach ($rows as $key => $value) {
            $rowsmcount[(int)$key] = count($value);
        }

        for ($i = 1; $i <= 12; $i++) {
            if (!empty($rowsmcount[$i])) {
                $rowsArr[$i] = $rowsmcount[$i];
            } else {
                $rowsArr[$i] = 0;
            }
        }

        return array_values($rowsArr);
    }
}