<?php

use App\Helpers\ResponseError;

$e = new ResponseError;

return [

	/*
	|--------------------------------------------------------------------------
	| Pagination Language Lines
	|--------------------------------------------------------------------------
	|
	| The following language lines are used by the paginator library to build
	| the simple pagination links. You are free to change them to anything
	| you want to customize your views to better match your application.
	|
	*/

	$e::NO_ERROR  => 'Başarılı',
	$e::ERROR_100 => 'Kullanıcı Bağlı Değil',
	$e::ERROR_101 => 'Kullanıcı Uygun Rollere Sahip Değil.',
	$e::ERROR_102 => 'Kullanıcı Adı ve ya Şifre Yanlış.',
	$e::ERROR_103 => 'E-posta Adresi Doğrulanmadı.',
	$e::ERROR_104 => 'Telefon Numarası Doğrulanmadı.',
	$e::ERROR_105 => 'Hesap Doğrulanmadı.',
	$e::ERROR_106 => 'Kullanıcı Zaten Mevcut.',
	$e::ERROR_107 => 'Lütfen Facebook veya Google ile bağlanın',
	$e::ERROR_108 => 'Kullanıcının cüzdanı yok.',
	$e::ERROR_109 => 'Yetersiz cüzdan bakiyesi',
	$e::ERROR_110 => 'Bu kullanıcı rolünü güncelleyemezsiniz',
	$e::ERROR_111 => 'Yalnızca :quantity products kadar alabilirsiniz',
	$e::ERROR_112 => 'when status: :verify Metin eklemelisiniz $verify_code Ana metin ve alt metine',
	$e::ERROR_113 => 'Kuryenin cüzdanı yok',
	$e::ERROR_114 => 'Satıcının cüzdanı yok',
	$e::ERROR_115 => 'Cihaz bulunamadı',
	$e::ERROR_116 => 'Reklamlar zaten oluşturuldu',
	$e::ERROR_117 => 'Telefon gerekli',
	$e::ERROR_118 => 'İşletme kapalı',
	$e::ERROR_119 => 'Stok çoğalt',

	$e::ERROR_201 => 'Yanlış SMS kodu',
	$e::ERROR_202 => 'Çok fazla talep yolladınız, lütfen sonra tekrar deneyin',
	$e::ERROR_203 => 'SMS kodu zaman aşımına uğradı',

	$e::ERROR_204 => 'Şu an satıcı değilsiniz veya mağazanız henüz oluşturulmadı.',
	$e::ERROR_205 => 'İşletme zaten oluşturuldu',
	$e::ERROR_206 => 'Kullanıcının zaten işletmesi var',
	$e::ERROR_207 => 'İşletme satıcısı güncellenemez',
	$e::ERROR_208 => 'Zaten abonesiniz',
	$e::ERROR_209 => 'İşletmenin teslimat bölgesi zaten oluşturuldu',
	$e::ERROR_210 => 'Teslimat zaten bağlandı',
	$e::ERROR_211 => 'Geçersiz teslimatçı veya belirtilen belirteç bulunamadı',
	$e::ERROR_212 => 'Bu sizin işletmeniz değil. Diğer hesabınızı kontrol edin',
	$e::ERROR_213 => 'Aboneliğiniz şu tarihten itibaren sona erdi',
	$e::ERROR_214 => 'Abonelik ürün sınırlaman süresi doldu',
	$e::ERROR_215 => 'Hatalı kod veya belirteç süresi doldu',
	$e::ERROR_216 => 'Gönderilen doğrulama kodunu girin',
	$e::ERROR_217 => 'Kullanıcı e-posta gönderdi',

	$e::ERROR_249 => 'Geçersiz kupon',
	$e::ERROR_250 => 'Kuponun süresi doldu',
	$e::ERROR_251 => 'Kupon zaten kullanıldı',
	$e::ERROR_252 => 'Durum zaten kullanıldı',
	$e::ERROR_253 => 'Hatalı durum türü',
	$e::ERROR_254 => 'İptal durumunu güncellenemiyor',
	$e::ERROR_255 => 'Sipariş zaten yolda veya teslim edildiyse Sipariş Durumu güncellenemez',

	$e::ERROR_400 => 'Hatalı istek',
	$e::ERROR_401 => 'İzinsiz',
	$e::ERROR_403 => 'Projeniz etkinleştirilmemiş',
	$e::ERROR_404 => 'Ürün bulunamadı',
	$e::ERROR_415 => 'Veri tabanı ile bağlantı sağlanamıyor',
	$e::ERROR_422 => 'Doğrulama Hatası',
	$e::ERROR_429 => 'İstek limiti aşıldı',
	$e::ERROR_430 => 'Stok miktarı 0',
	$e::ERROR_431 => 'Varsayılan etkin para birimi bulunamadı',
	$e::ERROR_430 => 'Stok miktarı 0',
	$e::ERROR_432 => 'Tanımsız Tür',
	$e::ERROR_433 => 'Poligon içerisinde değil',
	$e::ERROR_434 => 'Ödeme türü sadece cüzdan veya nakit olmalıdır',
	$e::ERROR_440 => 'Diğer işletme',

	$e::ERROR_501 => 'Oluşturulurken hata oluştu',
	$e::ERROR_502 => 'Güncelleme esnasında hata oluştu',
	$e::ERROR_503 => 'Silinirken hata oluştu',
	$e::ERROR_504 => 'Değerlere sahip kaydı silinemiyor',
	$e::ERROR_505 => 'Varsayılan kaydı silinemiyor # :ids',
	$e::ERROR_506 => 'Zaten mevcut',
	$e::ERROR_507 => 'Ürünlere sahip kayıt silinemiyor',
	$e::ERROR_508 => 'Excel formatı yanlış veya veriler geçersiz',
	$e::ERROR_509 => 'Geçersiz tarih formatı',
	$e::ERROR_510 => 'Adres hatalı',


	$e::CONFIRMATION_CODE               => 'Onay kodu :code',
	$e::NEW_ORDER                       => 'Yeni siparişiniz # :id',
	$e::PHONE_OR_EMAIL_NOT_FOUND        => 'Telefon veya E-posta bulunamadı',
	$e::ORDER_NOT_FOUND                 => 'Sipariş bulunamadı',
	$e::ORDER_REFUNDED                  => 'Sipariş geri ödendi',
	$e::ORDER_PICKUP                    => 'Sipariş hazır',
	$e::SHOP_NOT_FOUND                  => 'İşletme bulunamadı',
	$e::OTHER_SHOP                      => 'Diğer işletme',
	$e::SHOP_OR_DELIVERY_ZONE           => 'Boş mağaza veya teslimat bölgesi',
	$e::NOT_IN_POLYGON                  => 'Seçilen konuma teslimat mümkün değil',
	$e::NOT_IN_PARCEL_POLYGON           => 'Hizmetimiz bu mesafede hizmet vermiyor, lütfen başka bir tür veya başka bir adres seçin. Limit :km km',
	$e::CURRENCY_NOT_FOUND              => 'Para birimi bulunamadı',
	$e::LANGUAGE_NOT_FOUND              => 'Dil bulunamadı',
	$e::CANT_DELETE_ORDERS              => 'Siparişler silinemiyor :ids',
	$e::CANT_UPDATE_ORDERS              => 'Siparişler güncellenemiyor :ids',
	$e::ADD_CASHBACK                    => 'Para iadesi eklendi',
	$e::STATUS_CHANGED                  => 'Sipariş durumunuz güncellendi :status',
	$e::SHOP_APPROVED                   => 'Mağazanız onaylandı',
	$e::BOOKING_STATUS_CHANGED          => 'Rezervasyon durumunuz güncellendi :status',
	$e::PAYOUT_ACCEPTED                 => 'Ödeme zaten yapıldı :status',
	$e::CANT_DELETE_IDS                 => 'Silinemiyor :ids',
	$e::USER_NOT_FOUND                  => 'Kullanıcı bulunamadı',
	$e::USER_IS_BANNED                  => 'Kullanıcı yasaklandı!',
	$e::INCORRECT_LOGIN_PROVIDER        => 'Lütfen Facebook veya Google ile giriş yapın.',
	$e::FIN_FO                          => 'PHP dosyasında \'info\' uzantısına ihtiyacınız var',
    $e::USER_SUCCESSFULLY_REGISTERED    => 'Kullanıcı başarıyla kayıt oldu',
    $e::USER_CARTS_IS_EMPTY             => 'Kullanıcının sepeti boş',
    $e::PRODUCTS_IS_EMPTY               => 'Ürünler boş',
    $e::RECORD_WAS_SUCCESSFULLY_CREATED => 'Kayıt başarıyla oluşturuldu',
    $e::RECORD_WAS_SUCCESSFULLY_UPDATED => 'Kayıt başarıyla güncellendi',
    $e::RECORD_WAS_SUCCESSFULLY_DELETED => 'Kayıt başarıyla silindi',
    $e::IMAGE_SUCCESSFULLY_UPLOADED     => 'Başarılı :title, :type',
    $e::EMPTY_STATUS                    => 'Durum boş',
    $e::SUCCESS                         => 'Başarılı',
    $e::DELIVERYMAN_IS_NOT_CHANGED      => 'Teslimat görevlisini değiştirmeniz gerekiyor',
    $e::CATEGORY_IS_PARENT              => 'Üst Kategori',
    $e::ATTACH_FOR_ADDON                => 'Ek hizmet için ürün ekleyemezsiniz',
    $e::TYPE_PRICE_USER                 => 'Tür, fiyat veya kullanıcı boş',
    $e::NOTHING_TO_UPDATE               => 'Güncellenecek bir şey yok',
    $e::WAITER_NOT_EMPTY                => 'Boşta garson yok',
    $e::EMPTY                           => 'Boş',

    $e::ORDER_OR_DELIVERYMAN_IS_EMPTY   => 'Sipariş bulunamadı veya teslimat görevlisi eklenmemiş',
    $e::TABLE_BOOKING_EXISTS            => 'Bu masanın rezervasyonu yapılmış. From :start_date to :end_date',
    $e::DELIVERYMAN_SETTING_EMPTY       => 'Ayarlarınız boş',
    $e::NEW_BOOKING                     => 'Yeni rezervasyon',

];
