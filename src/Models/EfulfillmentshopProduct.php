<?php

namespace Qubiqx\QcommerceEcommerceEfulfillmentshop\Models;

use Illuminate\Database\Eloquent\Model;
use Qubiqx\QcommerceEcommerceCore\Models\Product;
use Spatie\Activitylog\Traits\LogsActivity;

class EfulfillmentshopProduct extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'qcommerce__product_efulfillmentshop';

    protected $fillable = [
        'product_id',
        'efulfillment_shop_id',
        'error',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
