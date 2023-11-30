<?php

namespace App\Services\PaymentService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\Subscription;
use Exception;
use Http;
use Illuminate\Database\Eloquent\Model;
use Str;
use Throwable;

class PayTabsService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws Throwable
     */
    public function orderProcessTransaction(array $data): Model|PaymentProcess
    {
        $payment        = Payment::where('tag', 'paytabs')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $order          = Order::find(data_get($data, 'order_id'));

        $totalPrice = ceil($order->rate_total_price);

        $order->update([
            'total_price' => $totalPrice
        ]);

        $host = request()->getSchemeAndHttpHost();
        $url  = "$host/order-stripe-success?" . (
            data_get($data, 'parcel_id') ? "parcel_id=$order->id" : "order_id=$order->id"
        );

        $headers = [
            'Accept' 		=> 'application/json',
            'Content-Type' 	=> 'application/json',
            'authorization' => data_get($payload, 'server_key')
        ];

        $trxRef = "$order->id-" . time();

        $currency = Str::upper($order->currency?->title ?? data_get($payload, 'currency'));

        if(!in_array($currency, ['AED','EGP','SAR','OMR','JOD','US'])) {
            throw new Exception(__('errors.' . ResponseError::CURRENCY_NOT_FOUND, locale: $this->language));
        }

        $request = Http::withHeaders($headers)->post('https://secure.paytabs.sa/payment/request', [
            'profile_id'        => data_get($payload, 'profile_id'),
            'tran_type'         => 'sale',
            'tran_class'        => 'ecom',
            'cart_id'        	=> $trxRef,
            'cart_description'  => $order->note ?? "payment for order #$order->id",
            'cart_currency'  	=> $currency,
            'cart_amount'  		=> $totalPrice,
            'callback'          => "$host/api/v1/webhook/paytabs/payment",
            'return'          	=> $url,
        ]);

        $body = $request->json();

        if (!in_array($request->status(), [200, 201])) {
            throw new Exception(data_get($body, 'message'));
        }

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
			'model_id'   => $order->id,
			'model_type' => get_class($order)
        ], [
            'id' => $trxRef,
            'data' => [
                'url'  	=> data_get($body, 'redirect_url'),
                'price'	=> $totalPrice,
            ]
        ]);
    }

    /**
     * @param array $data
     * @param Shop $shop
     * @param $currency
     * @return Model|array|PaymentProcess
     * @throws Exception
     */
    public function subscriptionProcessTransaction(array $data, Shop $shop, $currency): Model|array|PaymentProcess
    {
        $payment = Payment::where('tag', 'paytabs')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $host           = request()->getSchemeAndHttpHost();

        /** @var Subscription $subscription */
        $subscription   = Subscription::find(data_get($data, 'subscription_id'));

		$headers = [
			'Accept' 		=> 'application/json',
			'Content-Type' 	=> 'application/json',
			'authorization' => data_get($payload, 'server_key')
		];

		$trxRef = "$subscription->id-" . time();

		$currency = Str::upper(data_get($payload, 'currency', $currency));

		if(!in_array($currency, ['AED','EGP','SAR','OMR','JOD','US'])) {
			throw new Exception(__('errors.' . ResponseError::CURRENCY_NOT_FOUND, locale: $this->language));
		}

		$request = Http::withHeaders($headers)->post('https://secure.paytabs.sa/payment/request', [
			'profile_id'        => data_get($payload, 'profile_id'),
			'tran_type'         => 'sale',
			'tran_class'        => 'ecom',
			'cart_id'        	=> $trxRef,
			'cart_description'  => "seller subscription",
			'cart_currency'  	=> $currency,
			'cart_amount'  		=> ceil($subscription->price),
			'callback'          => "$host/api/v1/webhook/paytabs/payment",
			'return'          	=> "$host/subscription-stripe-success?subscription_id=$subscription->id",
		]);

		$body = $request->json();

		if (!in_array($request->status(), [200, 201])) {
			throw new Exception(data_get($body, 'message'));
		}

		return PaymentProcess::updateOrCreate([
			'user_id'    => auth('sanctum')->id(),
			'model_id'   => $subscription->id,
			'model_type' => get_class($subscription)
		], [
			'id' => $trxRef,
			'data' => [
				'url'  			  => data_get($body, 'redirect_url'),
				'price'           => ceil($subscription->price) * 100,
				'shop_id'         => $shop->id,
				'subscription_id' => $subscription->id,
			]
		]);
    }
}
