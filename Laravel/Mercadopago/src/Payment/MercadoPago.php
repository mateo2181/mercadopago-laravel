<?php

namespace Laravel\Mercadopago\Payment;

use Illuminate\Support\Facades\Config;
use Webkul\Payment\Payment\Payment;
/**
 * Mercado Pago payment method class
 *
 * @author    Mateo Merlo
 * @copyright 2018 Mateo
 */
class MercadoPago extends Payment
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'mercadopago';

    protected function addPayer(&$fields)
    {
        $cart = $this->getCart();

        $billingAddress = $cart->billing_address;

        $fields['billing_address']['first_name'] = $billingAddress->first_name;
        $fields['billing_address']['last_name'] = $billingAddress->last_name;
        $fields['billing_address']['email'] = $billingAddress->email;
        $fields['billing_address']['address'] = $billingAddress->address1;
        $fields['billing_address']['country'] = $billingAddress->country;
        $fields['billing_address']['state'] = $billingAddress->state;
        $fields['billing_address']['city'] = $billingAddress->city;
        $fields['billing_address']['postcode'] = $billingAddress->postcode;
        $fields['billing_address']['phone'] = $billingAddress->phone;
    }

    /**
     * Add order item fields
     *
     * @param array $fields
     * @param int $i
     * @return void
     */
    protected function addLineItemsFields(&$fields, $i = 1)
    {
        $cartItems = $this->getCartItems();
        $cart = $this->getCart();

        foreach ($cartItems as $i => $item) {
                $fields['items'][$i]['id'] = $item->sku;
                $fields['items'][$i]['title'] = $item->name;
                $fields['items'][$i]['quantity'] = $item->quantity;
                $fields['items'][$i]['currency_id'] = $cart->cart_currency_code;
                $fields['items'][$i]['unit_price'] = $item->price;
        }
    }

    /**
     * Return form field array
     *
     * @return array
     */
    public function getFormFields()
    {
        $cart = $this->getCart();

        $fields = [
            'business'         => $this->getConfigData('business_account'),
            'invoice'          => $cart->id,
            'amount'           => $cart->sub_total,
            'tax'              => $cart->tax_total,
            'discount_amount'  => $cart->discount,
            'notification_url' => route('mercadopago.ipn'),
            'external_reference' => $cart->id
        ];

        $fields['back_urls']['success'] = route('mercadopago.success');
        $fields['back_urls']['failure'] = route('mercadopago.failure');
        $fields['back_urls']['pending'] = route('mercadopago.pending');
    

        $this->addLineItemsFields($fields);
        $this->addShipping($fields);
        $this->addPayer($fields);

        return $fields;
    }


    /**
     * Add shipping as item
     *
     * @param array $fields
     * @param int $i
     * @return void
     */
    protected function addShipping(&$fields)
    {
        $cart = $this->getCart();
        $shippingAddress = $cart->shipping_address;

        $fields['shipping_method']['name'] = $cart->selected_shipping_rate->carrier_title;
        $fields['shipping_method']['price'] = $cart->selected_shipping_rate->price;

        $fields['shipping_address']['first_name'] = $shippingAddress->first_name;
        $fields['shipping_address']['last_name'] = $shippingAddress->last_name;
        $fields['shipping_address']['email'] = $shippingAddress->email;
        $fields['shipping_address']['address'] = $shippingAddress->address1;
        $fields['shipping_address']['country'] = $shippingAddress->country;
        $fields['shipping_address']['state'] = $shippingAddress->state;
        $fields['shipping_address']['city'] = $shippingAddress->city;
        $fields['shipping_address']['postcode'] = $shippingAddress->postcode;
        $fields['shipping_address']['phone'] = $shippingAddress->phone;
    }

    public function getRedirectUrl()
    {
        return route('mercadopago.redirect');
    }
}