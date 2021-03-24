<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
    public $langs = [
        'ru',
        'uk',
        'en',
        'bg',
    ];
    protected $appends = ['name'];

    public function __construct(array $attributes = [])
    {
        foreach ($this->langs as $lang) {
            $this->fillable[] = 'name_' . $lang;
        }
        parent::__construct($attributes);
    }

    public function getNameAttribute()
    {
        return $this->attributes['name_' . config('app.locale')] ?? '';
    }
}
