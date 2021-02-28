<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use SoftDeletingTrait;

    protected $table = 'favorite';
    public $timestamps = true;

    protected $dates = ['deleted_at'];

}
