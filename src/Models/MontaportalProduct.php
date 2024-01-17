<?php

namespace Dashed\DashedEcommerceMontaportal\Models;

use Dashed\DashedEcommerceCore\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MontaportalProduct extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'dashed__product_montaportal';

    protected $fillable = [
        'product_id',
        'montaportal_id',
        'sync_stock',
        'error',
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
