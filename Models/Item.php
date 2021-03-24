<?php

namespace App;

use App\Jobs\UpdateDatesTable;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use Time;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'room_id',
        'order_id',
        'date',
        'time',
        'players',
        'price',
        'item_price',
    ];

    public static function boot()
    {
        parent::boot();

        self::updated(function ($model) {
            $order = $model->order()->with('items')->first();
            $order->updateDateAndPrice();
        });

        self::deleting(function ($model) {
            $model->services()->detach();
        });

        self::deleted(function ($model) {
            UpdateDatesTable::dispatch($model->room_id);
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function services()
    {
        return $this->belongsToMany(HistoryService::class, 'item_service', 'item_id', 'service_id');
    }

    public function calculatePrice()
    {
        $services = $this->services;
        $price = $this->price;
        foreach ($services as $service) {
            $price += $service->price;
        }
        $this->item_price = $price;
        $this->save();
    }
}
