<?php

namespace App\Services\PaymentService;

use App\Models\Language;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\SubscriptionService\SubscriptionService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Payment;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\PaymentChannel;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\ThreedsInitialize;
use Iyzipay\Options;
use Iyzipay\Request\CreatePaymentRequest;
use Log;
use Str;

class IyzicoService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

	/**
	 * @throws Exception
	 */
	public function orderProcessTransaction(array $data)
	{
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		/** @var Order|ParcelOrder $order */
        $order = data_get($data, 'parcel_id')
            ? ParcelOrder::with(['user', 'type', 'currency'])->find(data_get($data, 'parcel_id'))
            : Order::with([
				'user.tokens',
				'currency',
				'orderDetails.stock.stockExtras',

				'orderDetails.stock.countable.translation' => fn($q) => $q
					->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),

				'orderDetails.stock.countable.category.translation' => fn($q) => $q
					->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),

			])->find(data_get($data, 'order_id'));

		$totalPrice = ceil($order->rate_total_price);

		$order->update([
			'total_price' => $totalPrice
		]);

		$address = data_get(get_class($order) === Order::class ? $order->address : $order->address_to, 'address');
        $host = request()->getSchemeAndHttpHost();

		$url  = "$host/order-stripe-success?" . (
			data_get($data, 'parcel_id') ? "parcel_id=$order?->id" : "order_id=$order?->id"
			);

		if (!in_array($this->language, [Locale::TR, Locale::EN])) {
			$this->language = Locale::TR;
		}

		$currency = Str::upper($order->currency?->title);

		$currencies = [
			Currency::TL,
			Currency::EUR,
			Currency::USD,
			Currency::GBP,
			Currency::IRR,
			Currency::NOK,
			Currency::RUB,
			Currency::CHF,
		];

		if (!in_array($currency, $currencies)) {
			throw new Exception("currency $currency is not supported");
		}

		$id = time();

		$options = new Options();
		$options->setApiKey('');
		$options->setSecretKey('');
		$options->setBaseUrl('https://api.iyzipay.com'); //https://api.iyzipay.com https://sandbox-api.iyzipay.com

//		$request = new CreateSubMerchantRequest();
//		$request->setLocale(Locale::TR);
//		$request->setConversationId($id);
//		$request->setSubMerchantExternalId(Str::uuid());
//		$request->setSubMerchantType(SubMerchantType::PRIVATE_COMPANY);
//		$request->setAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
//		$request->setTaxOffice("Tax office");
//		$request->setLegalCompanyTitle("John Doe inc");
//		$request->setEmail("info@yetis.app");
//		$request->setGsmNumber("+905350000000");
//		$request->setName("John's market");
//		$request->setIban("TR180006200119000006672315");
//		$request->setIdentityNumber("31300864726");
//		$request->setCurrency(Currency::TL);
//		$subMerchant = SubMerchant::create($request, $options);
//
//		dd($subMerchant);

		$request = new CreatePaymentRequest();
		$request->setLocale($this->language);
		$request->setConversationId($id);
		$request->setPrice($totalPrice);
		$request->setPaidPrice($totalPrice);
		$request->setCurrency($currency);
		$request->setInstallment(1);
		$request->setBasketId($order?->id);
		$request->setPaymentGroup(PaymentGroup::PRODUCT);
		$request->setCallbackUrl($url);
		$request->setPaymentChannel(data_get($data, 'chanel', PaymentChannel::WEB));

		$paymentCard = new PaymentCard();
		$paymentCard->setCardHolderName(data_get($data, 'holder_name'));
		$paymentCard->setCardNumber(data_get($data, 'card_number'));
		$paymentCard->setExpireMonth(data_get($data, 'expire_month'));
		$paymentCard->setExpireYear(data_get($data, 'expire_year'));
		$paymentCard->setCvc(data_get($data, 'cvc'));
		$paymentCard->setRegisterCard(0);
		$request->setPaymentCard($paymentCard);

		$buyer = new Buyer();
		$buyer->setId($order->user->id);
		$buyer->setName($order->user->firstname ?? 'firstname');
		$buyer->setSurname($order->user->lastname ?? 'lastname');
		$buyer->setGsmNumber('+' . (str_replace('+', '', $order->user->phone)));
		$buyer->setEmail($order->user->email);
		$buyer->setIdentityNumber("Buyer-{$order->user->id}");
		$buyer->setLastLoginDate($order->user->tokens->first()?->last_used_at?->format('Y-m-d H:i:s'));
		$buyer->setRegistrationDate($order->user->created_at?->format('Y-m-d H:i:s'));
		$buyer->setRegistrationAddress($address ?? "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
		$buyer->setIp(request()->ip());
		$buyer->setCity("Istanbul");
		$buyer->setCountry("Turkey");
		$buyer->setZipCode("34732");
		$request->setBuyer($buyer);

		$shippingAddress = new Address();
		$shippingAddress->setContactName("{$order->user->firstname} {$order->user->lastname}");
		$shippingAddress->setCity("Istanbul");
		$shippingAddress->setCountry("Turkey");
		$shippingAddress->setAddress($address ?? "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
		$shippingAddress->setZipCode("34742");
		$request->setShippingAddress($shippingAddress);

		$billingAddress = new Address();
		$billingAddress->setContactName("{$order->user->firstname} {$order->user->lastname}");
		$billingAddress->setCity("Istanbul");
		$billingAddress->setCountry("Turkey");
		$billingAddress->setAddress($address ?? "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
		$billingAddress->setZipCode("34742");
		$request->setBillingAddress($billingAddress);

		$basketItems = [];

		if (get_class($order) === Order::class) {

			foreach ($order->orderDetails as $orderDetail) {

				$extraText = '';

				foreach ($orderDetail->stock?->stockExtras ?? [] as $extraKey => $stockExtra) {

					if ($extraKey !== 0) {
						$extraText .= ' ';
					}

					$extraText .= $stockExtra->value;
				}

				$firstBasketItem = new BasketItem();
				$firstBasketItem->setId($orderDetail->id);
				$firstBasketItem->setName("{$orderDetail->stock?->countable?->translation?->title}, $extraText");
				$firstBasketItem->setCategory1($orderDetail->stock?->countable?->category?->translation?->title);
				$firstBasketItem->setCategory2($orderDetail->stock?->countable?->category?->translation?->title);
				$firstBasketItem->setItemType(BasketItemType::PHYSICAL);
				$firstBasketItem->setPrice($totalPrice);
				$firstBasketItem->setSubMerchantKey('');
				$firstBasketItem->setSubMerchantPrice($totalPrice);
				$basketItems[] = $firstBasketItem;
			}

		} else {
			$firstBasketItem = new BasketItem();
			$firstBasketItem->setId($order->id);
			$firstBasketItem->setName($order?->type?->type ?? 'box');
			$firstBasketItem->setCategory1($order?->type?->type ?? 'box');
			$firstBasketItem->setCategory2($order?->type?->type ?? 'box');
			$firstBasketItem->setItemType(BasketItemType::PHYSICAL);
			$firstBasketItem->setPrice($totalPrice);
			$firstBasketItem->setSubMerchantKey('');
			$firstBasketItem->setSubMerchantPrice($totalPrice);
			$basketItems[] = $firstBasketItem;
		}

		$request->setBasketItems($basketItems);

		$request = ThreedsInitialize::create($request, $options);

		$json = htmlspecialchars_decode($request->getRawResult(), true);
		$data = json_decode($json);

		if ($request->getErrorCode()) {
			throw new Exception($request->getErrorMessage(), $request->getErrorCode());
		}

		return view('iyzico', ['result' => $data->threeDSHtmlContent]);
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
        $subscription = Subscription::find(data_get($data, 'subscription_id'));

		if (!in_array($this->language, [Locale::TR, Locale::EN])) {
			$this->language = Locale::TR;
		}

		$currency = Str::upper($currency);

		$currencies = [
			Currency::TL,
			Currency::EUR,
			Currency::USD,
			Currency::GBP,
			Currency::IRR,
			Currency::NOK,
			Currency::RUB,
			Currency::CHF,
		];

		if (!in_array($currency, $currencies)) {
			throw new Exception("currency $currency is not supported");
		}

		$id = time();

		$options = new Options();
		$options->setApiKey('n5Z8qtHCimMwEiWVWQxvf7zFzCeaUG07');
		$options->setSecretKey('IoZE9qyHOck56RU6oqBhoI9joeyiG2wb');
		$options->setBaseUrl('https://api.iyzipay.com');

		$request = new CreatePaymentRequest();
		$request->setLocale($this->language);
		$request->setConversationId($id);
		$request->setPrice($subscription->price);
		$request->setPaidPrice($subscription->price);
		$request->setCurrency($currency);
		$request->setInstallment(1);
		$request->setBasketId($subscription?->id);
		$request->setPaymentGroup(PaymentGroup::PRODUCT);
		$request->setPaymentChannel(data_get($data, 'chanel', PaymentChannel::WEB));

		$paymentCard = new PaymentCard();
		$paymentCard->setCardHolderName(data_get($data, 'holder_name'));
		$paymentCard->setCardNumber(data_get($data, 'card_number'));
		$paymentCard->setExpireMonth(data_get($data, 'expire_month'));
		$paymentCard->setExpireYear(data_get($data, 'expire_year'));
		$paymentCard->setCvc(data_get($data, 'cvc'));
		$paymentCard->setRegisterCard(0);
		$request->setPaymentCard($paymentCard);

		$buyer = new Buyer();
		$buyer->setId($shop->seller->id);
		$buyer->setName($shop->seller->firstname);
		$buyer->setSurname($shop->seller->lastname);
		$buyer->setGsmNumber('+' . (str_replace('+', '', $shop->seller->phone)));
		$buyer->setEmail($shop->seller->email);
		$buyer->setIdentityNumber("Buyer-$shop->user_id");
		$buyer->setLastLoginDate($shop->seller->tokens->first()?->last_used_at?->format('Y-m-d H:i:s'));
		$buyer->setRegistrationDate($shop->seller->created_at?->format('Y-m-d H:i:s'));
		$buyer->setRegistrationAddress($address ?? "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
		$buyer->setIp(request()->ip());
		$buyer->setCity("Istanbul");
		$buyer->setCountry("Turkey");
		$buyer->setZipCode("34732");
		$request->setBuyer($buyer);

		$shippingAddress = new Address();
		$shippingAddress->setContactName("{$shop->seller->firstname} {$shop->seller->firstname}");
		$shippingAddress->setCity("Istanbul");
		$shippingAddress->setCountry("Turkey");
		$shippingAddress->setAddress($address ?? "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
		$shippingAddress->setZipCode("34742");
		$request->setShippingAddress($shippingAddress);

		$billingAddress = new Address();
		$billingAddress->setContactName("{$shop->seller->firstname} {$shop->seller->lastname}");
		$billingAddress->setCity("Istanbul");
		$billingAddress->setCountry("Turkey");
		$billingAddress->setAddress($address ?? "Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
		$billingAddress->setZipCode("34742");

		$request->setBillingAddress($billingAddress);

		$basketItems = [];

		$firstBasketItem = new BasketItem();
		$firstBasketItem->setId($subscription->id);
		$firstBasketItem->setName('subscription');
		$firstBasketItem->setCategory1('subscription');
		$firstBasketItem->setCategory2('subscription');
		$firstBasketItem->setItemType(BasketItemType::VIRTUAL);
		$firstBasketItem->setPrice($subscription->price);
		$firstBasketItem->setSubMerchantKey('G9iTu18jz0k2mWotw3TsJsbbkyA=');
		$firstBasketItem->setSubMerchantPrice($subscription->price);
		$basketItems[] = $firstBasketItem;
		$request->setBasketItems($basketItems);

		$request = Payment::create($request, $options);

		if ($request->getErrorCode()) {
			throw new Exception($request->getErrorMessage(), $request->getErrorCode());
		}

		Log::error('iyzico', [$request]);

		if ($request->getStatus()) {

			$shopSubscription = (new SubscriptionService)->subscriptionAttach(
				$subscription,
				$shop->id,
				$request->getStatus() === 'success' ? 1 : 0
			);

			$shopSubscription->transaction?->update([
				'payment_trx_id' => $id,
				'status' => $request->getStatus() === 'success' ? Transaction::STATUS_PAID : Transaction::STATUS_PROGRESS,
			]);
		}

        return $subscription;
    }
}
