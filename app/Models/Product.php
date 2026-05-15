<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_name', 'partner_type', 'dsi_code', 'category', 'market_type',
        'sub_market_type', 'sub_payment_mode',
        'description', 'default_price', 'publish_rate', 'komisi', 'nett_price', 'unit_price_dsi',
        'unit', 'payment_mode', 'is_active', 'created_by',
        'parents_name', 'pax_type', 'bundle_adult_count', 'bundle_child_count',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Auto-classify pax columns from product_name on create/update
        static::saving(function (self $product) {
            if (!$product->isDirty('parents_name') && $product->isDirty('product_name')) {
                $product->autoClassifyPax();
            } elseif (empty($product->parents_name) && !empty($product->product_name)) {
                $product->autoClassifyPax();
            }
        });
    }

    protected function autoClassifyPax(): void
    {
        $parts  = explode(' - ', $this->product_name, 2);
        $parent = strtoupper(trim($parts[0]));
        $type   = isset($parts[1]) ? trim($parts[1]) : '';

        $isBundle = preg_match('/\d+\s*A\s*[&\s]\s*\d+\s*C/i', $type)
                 || preg_match('/\d+\s*ADULT/i', $type)
                 || preg_match('/ADULT\s*[\/&]\s*(AND\s*)?CHILD/i', $type);

        $adultCount = 0;
        $childCount = 0;

        if ($isBundle) {
            $paxType = 'BUNDLE';
            if (preg_match('/(\d+)\s*A\s*[&\s]+\s*(\d+)\s*C/i', $type, $m)) {
                $adultCount = (int) $m[1];
                $childCount = (int) $m[2];
            } else {
                if (preg_match('/(\d+)\s*ADULT/i', $type, $m)) { $adultCount = (int) $m[1]; }
                if (preg_match('/(\d+)\s*CHILD/i', $type, $m)) { $childCount = (int) $m[1]; }
            }
        } else {
            $hasAdult = stripos($type, 'adult') !== false;
            $hasChild = stripos($type, 'child') !== false;
            $paxType  = $hasAdult ? 'ADULT' : ($hasChild ? 'CHILD' : 'TICKET');
        }

        $this->parents_name       = $parent;
        $this->pax_type           = $paxType;
        $this->bundle_adult_count = $adultCount;
        $this->bundle_child_count = $childCount;
    }

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'default_price'  => 'decimal:2',
            'publish_rate'   => 'decimal:2',
            'komisi'         => 'decimal:2',
            'nett_price'     => 'decimal:2',
            'unit_price_dsi' => 'decimal:2',
        ];
    }

    public function aliases()
    {
        return $this->hasMany(ProductAlias::class);
    }
}
