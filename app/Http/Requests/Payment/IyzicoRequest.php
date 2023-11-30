<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use ReflectionClass;

class IyzicoRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
		$reflectionClass = new ReflectionClass('Iyzipay\Model\PaymentChannel');
		$constants = $reflectionClass->getConstants();

		return [
			'holder_name'  => 'required|string|min:5|max:255',
			'card_number'  => 'required|numeric',
			'expire_month' => 'required|numeric|max:12',
			'expire_year'  => 'required|int',
			'cvc' 		   => 'required|string|max:255',
			'chanel' 	   => 'required|string|in:' . implode(',', $constants),
            'order_id'  => [
                empty(request('parcel_id')) && empty(request('subscription_id')) ? 'required' : 'nullable',
                Rule::exists('orders', 'id')
            ],
            'parcel_id'  => [
                empty(request('order_id')) && empty(request('subscription_id')) ? 'required' : 'nullable',
                Rule::exists('parcel_orders', 'id')
            ],
			'subscription_id' => [
				empty(request('order_id')) && empty(request('parcel_id')) ? 'required' : 'nullable',
				Rule::exists('subscriptions', 'id')->where('active', 1)->whereNull('deleted_at')
			],
        ];
    }

}
