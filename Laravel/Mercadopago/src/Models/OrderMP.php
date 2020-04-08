<?php

namespace Laravel\Mercadopago\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Models\Order as Order;

class OrderMP extends Model
{
    protected $table = 'ordersMP';

    protected $fillable = [ 
                             'merchant_order_id', 
                             'payment_type',
                             'external_reference',
                             'status',
                             'order_id',
                             'created_at', 
                             'updated_at'
                            ];

    protected $statusLabel = [
        'pending' => 'Pendiente',
        'processing' => 'Pendiente',
        'completed' => 'Completo',
        'canceled' => 'Cancelado',
        'refunded' => 'Devuelto'
    ];

    public function getStatusLabelAttribute()
    {
        return $this->statusLabel[$this->status];
    }
    /**
     * Get the order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class,'order_id');
    }
}