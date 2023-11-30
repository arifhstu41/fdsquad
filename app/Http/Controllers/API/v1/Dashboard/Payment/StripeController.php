<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Http\Requests\Shop\SubscriptionRequest;
use App\Models\Currency;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\WalletHistory;
use App\Services\PaymentService\StripeService;
use App\Services\SubscriptionService\SubscriptionService;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Iyzipay\Model\Locale;
use Iyzipay\Model\ThreedsPayment;
use Iyzipay\Options;
use Iyzipay\Request\CreateThreedsPaymentRequest;
use Log;
use Redirect;
use Throwable;

class StripeController extends Controller
{
    use OnResponse, ApiResponse;

    public function __construct(private StripeService $service)
    {
        parent::__construct();
    }

    /**
     * process transaction.
     *
     * @param StripeRequest $request
     * @return JsonResponse
     */
    public function orderProcessTransaction(StripeRequest $request): JsonResponse
    {
        try {
            $result = $this->service->orderProcessTransaction($request->all());

            return $this->successResponse('success', $result);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'message' => ResponseError::ERROR_501
            ]);
        }

    }

    /**
     * process transaction.
     *
     * @param SubscriptionRequest $request
     * @return JsonResponse
     */
    public function subscriptionProcessTransaction(SubscriptionRequest $request): JsonResponse
    {
        $shop     = auth('sanctum')->user()?->shop ?? auth('sanctum')->user()?->moderatorShop;
        $currency = Currency::currenciesList()->where('active', 1)->where('default', 1)->first()?->title;

        if (empty($shop)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::SHOP_NOT_FOUND, locale: $this->language)
            ]);
        }

        if (empty($currency)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::CURRENCY_NOT_FOUND)
            ]);
        }

        try {
            $result = $this->service->subscriptionProcessTransaction($request->all(), $shop, $currency);

            return $this->successResponse('success', $result);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501)
            ]);
        }

    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function orderResultTransaction(Request $request): RedirectResponse
    {
		Log::error('$request->all()', $request->all());

		$orderId  = (int)$request->input('order_id');
		$parcelId = (int)$request->input('parcel_id');
		$subscriptionId = (int)$request->input('subscription_id');

		$to = config('app.front_url') . ($orderId ? "orders/$orderId" : "parcels/$parcelId");

		$options = new Options();
		$options->setApiKey('sandbox-AvmTJIuSiyUhXVxWsghlDMWA28smi0Lz'); //n5Z8qtHCimMwEiWVWQxvf7zFzCeaUG07
		$options->setSecretKey('sandbox-cPWCAnqE5B1A1LiyL76gr4GiqIcp1K6d'); //IoZE9qyHOck56RU6oqBhoI9joeyiG2wb
		$options->setBaseUrl('https://sandbox-api.iyzipay.com'); //https://api.iyzipay.com

		$threeds = new CreateThreedsPaymentRequest();
		$threeds->setLocale(Locale::TR);
		$threeds->setConversationId($request->input('conversationId'));
		$threeds->setPaymentId($request->input('paymentId'));
		$threeds->setConversationData($request->input('conversationData'));

		$threedsPayment = ThreedsPayment::create($threeds, $options);

		$status = match ($threedsPayment->getStatus()) {
			'success' => Transaction::STATUS_PAID,
			'failure' => Transaction::STATUS_CANCELED,
			default   => Transaction::STATUS_PROGRESS,
		};

		if ($orderId || $parcelId) {
			$order = $orderId ? Order::find($orderId) : ParcelOrder::find($parcelId);

			$order?->transaction?->update([
				'payment_trx_id' => $orderId ?? $parcelId,
				'status' 		 => $status,
			]);

			return Redirect::to($to);
		}

		if ($subscriptionId) {

			$shop = Shop::find($request->input('shop_id'));
			$subscription = Subscription::find($subscriptionId);

			$shopSubscription = (new SubscriptionService)->subscriptionAttach(
				$subscription,
				(int)$shop?->id,
				$status === Transaction::STATUS_PAID ? 1 : 0
			);

			$shopSubscription->transaction?->update([
				'payment_trx_id' => $subscriptionId,
				'status'         => $status,
			]);

			$to = config('app.admin_url') . "seller/subscriptions/$subscriptionId";

			return Redirect::to($to);
		}

        return Redirect::to($to);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function subscriptionResultTransaction(Request $request): RedirectResponse
    {
        $subscription = Subscription::find((int)$request->input('subscription_id'));

        $to = config('app.admin_url') . "seller/subscriptions/$subscription->id";

        return Redirect::to($to);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        Log::error('paymentWebHook', $request->all());
        $status = $request->input('data.object.status');

        $status = match ($status) {
            'succeeded' => WalletHistory::PAID,
            default     => 'progress',
        };

        $token = $request->input('data.object.id');

        $this->service->afterHook($token, $status);
    }

}
