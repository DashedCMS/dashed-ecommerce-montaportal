<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Models;

use Illuminate\Database\Eloquent\Model;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Spatie\Activitylog\Traits\LogsActivity;

class MontaportalOrder extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'qcommerce__order_montaportal';

    protected $fillable = [
        'order_id',
        'montaportal_id',
        'pushed_to_montaportal',
        'montaportal_pre_order_ids',
        'track_and_trace_links',
        'track_and_trace_present',
        'error',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'montaportal_pre_order_ids' => 'array',
        'track_and_trace_links' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
