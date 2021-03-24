<?php

namespace App\Http\Controllers;

use App\Order;
use App\Type;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends OrderController
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Factory|View
     */
    public function index(Request $request)
    {
        $now = Carbon::now()->format('Y-m-d');
        $data = $this->getOrders($request);
        $orders = $data['orders'];
        $cities = $data['cities'];

        $orders = $orders->where(function ($query) use ($now) {
            return $query->whereDate('orders.' . Order::CREATED_AT, $now)->orWhere('status', Order::NEW);
        })->filter($this->filters)->orderBy('status')
            ->sortable([Order::CREATED_AT => 'desc'])
            ->paginate($request->get('pagination', env('DEFAULT_PAGINATION', 20)));

        $this->data = array_merge($this->data, [
            'orders' => $orders,
            'types' => Type::whereHas('rooms')->get(),
            'filters' => $this->filters,
            'cities' => $cities->get(),
        ]);
        return view('dash.index', $this->data);
    }
}
