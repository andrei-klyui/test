<?php

namespace App;

use App\Jobs\UpdateColorPrice;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'room_id',
        'timeline_id',
        'players',
        'price',
        'color',
    ];

    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            dispatch(new UpdateColorPrice($model->id));
        });

        self::updated(function ($model) {
            dispatch(new UpdateColorPrice($model->id));
        });
    }

    public function timeline()
    {
        return $this->belongsTo(Timeline::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
