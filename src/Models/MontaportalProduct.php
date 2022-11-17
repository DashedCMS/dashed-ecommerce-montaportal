<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Models;

use Illuminate\Database\Eloquent\Model;
use Qubiqx\QcommerceEcommerceCore\Models\Product;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MontaportalProduct extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'qcommerce__product_montaportal';

    protected $fillable = [
        'product_id',
        'montaportal_id',
        'sync_stock',
        'error',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
