<?php

namespace App;

use App\ModelFilters\OrderFilter;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Kyslik\ColumnSortable\Sortable;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class Order extends Model
{
    use Time, Filterable, Sortable;

    /* Pay status */
    const NOTPAY = 0;
    const CASH = 1;
    const CARD = 2;

    /* status */
    const NEW = 0;
    const CONFIRM = 1;
    const CANCELED = 2;
    const DELETED = 3;
    /* zero-status */
    const ZERO = 4;

    const STATUSES = [
        self::NEW => 'Новая',
        self::CONFIRM => 'Подтв.',
        self::CANCELED => 'Отмененна',
        self::DELETED => 'Удаленна',
    ];

    const PAY_STATUSES = [
        self::NOTPAY => 'не оплачен',
        self::CASH => 'Оплачен нал..',
        self::CARD => 'Оплачен безнал.',
    ];
    public $sortable = [
        'id',
        'author',
        'pay_status',
        'price',
        'created_at',
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'comment',
        'user_id',

        'hash',
        'pay_status',

        'sms_remind',
        'sms_confirm',
        'sms_feedback',

        'email_confirm',
        'email_feedback',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->author_type = Auth::user() ? Auth::user()->id : 0;
            $model->phone = str_replace(' ', '', $model->phone);
        });

        self::updating(function ($model) {
            $model->user_id = Auth::user()->id ?? 0;
            $model->phone = str_replace(' ', '', $model->phone);
        });

        self::deleting(function ($model) {
            $items = $model->items()->get();
            foreach ($items as $item) {
                $item->delete();
            }
        });
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function scopeToStatus($query, $status)
    {
        $oldStatus[0] = self::ZERO;
        switch ($status) {
            case self::CONFIRM:
                $oldStatus[0] = self::NEW;
//                $oldStatus[1] = self::CANCELED;
                break;
            case self::CANCELED:
                $oldStatus[0] = self::NEW;
                break;
            case self::DELETED:
                $oldStatus[0] = self::CONFIRM;
                break;
        }

        return $query->whereIn('status', $oldStatus);
    }

    public function updateDateAndPrice()
    {
        $price = 0;
        $date = [];
        $items = $this->items;
        foreach ($items as $item) {
            $price += $item->item_price;
            $itemDate = implode('.', array_reverse(explode('-', $item->date)));
            $date[] = $itemDate . ' на ' . $this->toTime($item->time);
        }
        $this->price = $price;
        $this->date = implode('<br/>', $date);
        $this->save();
    }

    public function modelFilter()
    {
        return $this->provideFilter(OrderFilter::class);
    }

    public function getFormattedPhone()
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $swissNumberProto = $phoneUtil->parse($this->phone,'UA');
        $phone = $phoneUtil->format($swissNumberProto,PhoneNumberFormat::INTERNATIONAL);
        $handlerNumber = substr_replace($phone,' ',strlen($phone) - 2,0);
        return $handlerNumber;
    }
}
