<?php

namespace Laravel\Mercadopago\Repositories;

use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Eloquent\Repository;

class OrderMPRepository extends Repository
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return Mixed
     */

    function model()
    {
        return 'Laravel\Mercadopago\Models\OrderMP';
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
    
            $orderMP = $this->model->create($data);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        DB::commit();

        return $orderMP;
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public function cancel($orderId)
    {
        $order = $this->findOrFail($orderId);

        if (! $order->canCancel())
            return false;

        $this->updateOrderStatus($orderId,'canceled');    
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getByOrderId($orderId)
    {
        $orderMP = $this->model->where('order_id', $orderId)->limit(1)->first();
        $orderMP->load('order');
        return $orderMP;
    }

    public function getOrderByMerchantId($orderMerchantId)
    {
        $orderMP = $this->model->where('merchant_order_id', $orderMerchantId)->limit(1)->first();
        $orderMP->load('order');
        return $orderMP;
    }

    public function updateOrderStatusByMerchantId($orderMerchantId,$status)
    {
        $orderMP = $this->model->where('merchant_order_id', $orderMerchantId)->limit(1)->first();
        if(!$orderMP) {
            return false;
        }
        $this->updateOrderStatus($orderMP->id,$status);
        return true;
    }

    public function updateOrderStatus($orderId,$status) 
    {
        $order = $this->model->findOrFail($orderId);
        if($order->status != 'refunded' && $order->status != 'canceled') {
            $order->status = $status;
            $order->save();
        }

        return true;
    }

}