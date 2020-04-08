<?php

namespace Laravel\Mercadopago\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Laravel\Mercadopago\Repositories\OrderMPRepository;
use MercadoPago;
use Laravel\Mercadopago\Payment\MercadoPago as MercadoPagoPayment;

class MPController extends Controller
{
    /**
     * OrderRepository object
     *
     * @var array
     */
    protected $orderRepository;

    /**
     * OrderMPRepository object
     *
     * @var array
     */
    protected $orderMPRepository;

    /**
     * Create a new controller instance.
     *
     * @param  Webkul\Attribute\Repositories\OrderRepository  $orderRepository
     * @return void
     */
    public function __construct(OrderRepository $orderRepository, OrderMPRepository $orderMPRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->orderMPRepository = $orderMPRepository;
    }

    public function createPayment()
    {
        // var_dump(env("MP_CLIENT_ID"));
        MercadoPago\SDK::setClientId(env("MP_CLIENT_ID"));
        MercadoPago\SDK::setClientSecret(env("MP_CLIENT_SECRET"));

        # Create a preference object
        $preference = new MercadoPago\Preference();

        $paymentMP = new MercadoPagoPayment();
        $paymentData = $paymentMP->getFormFields();
        // dd($paymentData);

        $preference->back_urls = $paymentData['back_urls'];
        $preference->external_reference = $paymentData['external_reference'];
        $preference->notification_url = $paymentData['notification_url'];
        $preference->auto_return = "approved";

        # Create items
        $array = array();
        foreach ($paymentData['items'] as $key => $item) {
            $itemMP = new MercadoPago\Item();
            $itemMP->id = $item['id'];
            $itemMP->title = $item['title'];
            $itemMP->quantity = $item['quantity'];
            $itemMP->currency_id = $item['currency_id'];
            $itemMP->unit_price = $item['unit_price'];

            array_push($array, $itemMP);
        }
        $preference->items = $array;

        # Create a payer object
        $payer = new MercadoPago\Payer();
        $payer->name = $paymentData['billing_address']['first_name'];
        $payer->surname = $paymentData['billing_address']['last_name'];
        $payer->email = "test_user_45480144@testuser.com";
        $payer->phone = array(
            "area_code" => "",
            "number" => $paymentData['billing_address']['phone']
        );
        $payer->address = array(
            "street_name" => $paymentData['billing_address']['address'],
            "zip_code" => $paymentData['billing_address']['postcode']
        );
        $preference->payer = $payer;


        # Save and posting preference
        $preference->save();
        // dd($preference);
        $urlPayment = $preference->init_point;
        return redirect()->away($urlPayment);
    }

    public function generateUser()
    {
        MercadoPago\SDK::setAccessToken(env('MP_TOKEN_SANDBOX'));
        $body = array(
            "json_data" => array(
                "site_id" => "MLA"
            )
        );

        $result = MercadoPago\SDK::post('/users/test_user', $body);
        var_dump($result);
    }

    public function success(Request $request)
    {
        // Example response
        // url.com/?collection_id=4641022010&collection_status=approved&preference_id=421929490-cc1c3bc4-b83b-41fd-9abe-a5cf5d2243dd&external_reference=null&payment_type=credit_card&merchant_order_id=1003516814
        \Log::info("CollectionId: " . $request->collection_id);
        \Log::info("MerchantOrderId: " . $request->merchant_order_id);
        \Log::info("CollectionStatus" . $request->collection_status);
        \Log::info("****** MP: END SUCCESS METHOD ******");


        $order = $this->orderRepository->create(Cart::prepareDataForOrder());
        Cart::deActivateCart();

        // Creamos la orden de MP con los datos especificos de MercadoPago
        $dataMP = [
            'merchant_order_id' => $request->merchant_order_id,
            'payment_type' => $request->payment_type,
            'external_reference' => $request->external_reference,
            'order_id' => $order->id,
            'status' => 'pending'
        ];
        $this->orderMPRepository->create($dataMP);

        session()->flash('order', $order);
        return redirect()->route('shop.checkout.success');
    }

    public function pending(Request $request)
    {
        // Example response
        // url.com/?collection_id=4641022010&collection_status=approved&preference_id=421929490-cc1c3bc4-b83b-41fd-9abe-a5cf5d2243dd&external_reference=null&payment_type=credit_card&merchant_order_id=1003516814
        \Log::info("CollectionId: " + $request->collection_id);
        \Log::info("MerchantOrderId: " + $request->merchant_order_id);
    }

    public function failure(Request $request)
    {
        // Example response
        // url.com/?collection_id=4641022010&collection_status=approved&preference_id=421929490-cc1c3bc4-b83b-41fd-9abe-a5cf5d2243dd&external_reference=null&payment_type=credit_card&merchant_order_id=1003516814
        // \Log::info("CollectionId: " + $request->collection_id);
        session()->flash('error', 'Ha ocurrido un error al concretar el pago. Intente nuevamente');
        return redirect()->route('shop.checkout.onepage.index');
    }

    public function cancelOrder($orderId)
    {
        try {

            MercadoPago\SDK::setAccessToken(env('MP_TOKEN_PROD'));
            $orderMP = $this->orderMPRepository->getByOrderId($orderId);
            $merchant_order = MercadoPago\MerchantOrder::find_by_id($orderMP->merchant_order_id);

            \Log::info(count($merchant_order->payments));
            if (count($merchant_order->payments) > 0) {
                foreach ($merchant_order->payments as $payment) {
                    //         $p = MercadoPago\Payment::find_by_id($payment->id);
                    $refund = new MercadoPago\Refund();
                    $refund->metadata = "A total refund";
                    $refund->amount   = $payment->total_paid_amount;
                    $refund->payment_id = $payment->id;
                    $refund->save();
                }
            }

            if (!$merchant_order) {
                return false;
            }
            return;
        } catch (Exception $e) {
            echo $e;
            exit;
        }
    }

    public function ipn(Request $request)
    {
        \Log::info("IPN Recibido");
        if (isset($_GET["id"], $_GET["topic"])) {
            \Log::info("TOPIC: " . $_GET["topic"]);
            \Log::info("ID: " . $_GET["id"]);
        }

        MercadoPago\SDK::setAccessToken(env('MP_TOKEN_PROD'));
        // return response('OK', 201);

        if (!isset($_GET["id"], $_GET["topic"]) || !ctype_digit($_GET["id"])) {
            abort(404);
        }

        $merchant_order = null;
        $payment = null;

        switch ($_GET["topic"]) {
            case "payment":
                $payment = MercadoPago\Payment::find_by_id($_GET["id"]);
                // Get the payment and the corresponding merchant_order reported by the IPN.
                $merchant_order = MercadoPago\MerchantOrder::find_by_id($payment->order->id);
                break;
            case "merchant_order":
                $merchant_order = MercadoPago\MerchantOrder::find_by_id($_GET["id"]);
        }

        /* Si payment tiene status ="refunded" marcamos la ordenMP como refunded,
           siempre y cuando transaction_amount_refunded >= base_grand_total  
        */  
        if($payment && $payment->status == 'refunded') {
            $orderMP = $this->orderMPRepository->getOrderByMerchantId($payment->order->id);
            if($payment->transaction_amount_refunded >= $orderMP->order->sub_total) {
                $this->refundOrder($orderMP);
            }

        }
        // Get Orden and Update Status
        if (isset($merchant_order->external_reference)) {
            $external_reference_id = $merchant_order->external_reference;
            \Log::info("Referencia externa:" . $external_reference_id);
        }
        // $order = Order::findOrFail($external_reference_id);
        // link notification id
        // $order->mp_notification_id = $_GET["id"];

        // \Log::info("PAYMENTS:");
        // \Log::info(print_r($merchant_order->payments, true));
        $paid_amount = 0;
        if (isset($merchant_order->payments)) {
            foreach ($merchant_order->payments as $payment) {
                if ($payment->status == 'approved') {
                    $paid_amount += $payment->transaction_amount;
                }
            }
        }

        if (isset($merchant_order)) {
            // If the payment's transaction amount is equal (or bigger) than the merchant_order's amount you can release your items
            if ($paid_amount >= $merchant_order->total_amount) {
                // if (count($merchant_order->shipments)>0) { // The merchant_order has shipments
                //     \Log::info("MERCHANT ORDER:");
                //     \Log::info(print_r($merchant_order, true));
                //     if($merchant_order->shipments[0]['status'] == "ready_to_ship") {
                //         print_r("Totally paid. Print the label and release your item.");
                //         \Log::info("Total pagado. Pedido listo para enviar");
                //     }
                // } else { // The merchant_order don't has any shipments
                //     print_r("Totally paid. Release your item.");
                //     \Log::info("Total pagado.");
                // }
                $this->orderMPRepository->updateOrderStatusByMerchantId($merchant_order->id, 'completed');
                \Log::info("Total pagado.");
            } else {
                $this->orderMPRepository->updateOrderStatusByMerchantId($merchant_order->id, 'processing');
                \Log::info("No pagado");
            }
        }

        return response('OK', 201);
    }

    public function refundOrder($orderMP)
    {
        $this->orderMPRepository->updateOrderStatusByMerchantId($orderMP->merchant_order_id, 'refunded');
        $status = $this->orderRepository->cancel($orderMP->order->id);
        return $status;
    }
}
