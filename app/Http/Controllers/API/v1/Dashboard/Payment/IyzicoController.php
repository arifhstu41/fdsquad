<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\IyzicoRequest;
use App\Models\Currency;
use App\Models\Transaction;
use App\Services\PaymentService\IyzicoService;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;
use Throwable;

class IyzicoController extends Controller
{
    use OnResponse, ApiResponse;

    public function __construct(private IyzicoService $service)
    {
        parent::__construct();
    }

    /**
     * process transaction.
     *
     * @param IyzicoRequest $request
     * @return JsonResponse
     */
    public function orderProcessTransaction(IyzicoRequest $request): mixed
    {
        try {
			return $this->service->orderProcessTransaction($request->all());
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'message' => $e->getMessage(),
				'code'    => (string)$e->getCode()
            ]);
        }

    }

    /**
     * process transaction.
     *
     * @param IyzicoRequest $request
     * @return JsonResponse
     */
    public function subscriptionProcessTransaction(IyzicoRequest $request): JsonResponse
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
                'message' => $e->getMessage()
            ]);
        }

    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        Log::error('paymentWebHook', $request->all());

        $status = $request->input('status');

        $status = match ($status) {
            'SUCCESS' => Transaction::STATUS_PAID,
            'FAILURE' => Transaction::STATUS_CANCELED,
            default     => 'progress',
        };

        $token = $request->input('token');

        $this->service->afterHook($token, $status);
    }

}
