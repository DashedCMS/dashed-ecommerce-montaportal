<?php

namespace Dashed\DashedEcommerceMontaportal\Classes;

use Dashed\DashedEcommerceCore\Models\OrderProduct;
use Exception;
use Carbon\Carbon;
use Qubiqx\Montapacking\Client;
use Dashed\DashedCore\Classes\Mails;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedEcommerceCore\Models\OrderLog;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedEcommerceMontaportal\Mail\TrackandTraceMail;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalOrder;
use Dashed\DashedEcommerceMontaportal\Models\montaportalProduct;

class Montaportal
{
    public static function isConnected($siteId = null)
    {
        if (!$siteId) {
            $siteId = Sites::getActive();
        }

        try {
            $client = self::initialize($siteId);
            $response = $client->getHealth();

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    public static function initialize($siteId = null)
    {
        if (!$siteId) {
            $siteId = Sites::getActive();
        }

        return new Client(Customsetting::get('montaportal_username', $siteId), Customsetting::get('montaportal_password', $siteId));
    }

    public static function createProduct(Product $product)
    {
        if (!$product->ean) {
            dump('no ean');

            return false;
        }

        if ($product->montaportalProduct) {
            //            dump('already have monta product');

            return true;
        }

        $apiClient = self::initialize();

        try {
            $montaProduct = $apiClient->getProduct($product->sku);
        } catch (Exception $e) {
            try {
                $montaProduct = $apiClient->getProductByBarcode($product->ean);
            } catch (Exception $e) {
                $montaProduct = null;
            }
        }

        if ($montaProduct) {
            $montaportalProduct = new montaportalProduct();
            $montaportalProduct->product_id = $product->id;
            $montaportalProduct->montaportal_id = $montaProduct->Sku;
            $montaportalProduct->sync_stock = 0;
            $montaportalProduct->save();

            return true;
        } else {
            try {
                $response = $apiClient->addProduct([
                    'Sku' => $product->sku,
                    'Description' => $product->name,
                    'Barcodes' => [$product->ean],
                ]);

                if (!$response->Sku) {
                    Mails::sendNotificationToAdmins('Product #' . $product->id . ' failed to push to Montapackage');
                } else {
                    $montaportalProduct = new montaportalProduct();
                    $montaportalProduct->product_id = $product->id;
                    $montaportalProduct->montaportal_id = $response->Sku;
                    $montaportalProduct->save();
                }

                return true;
            } catch (Exception $e) {
                dump($e->getMessage());
                Mails::sendNotificationToAdmins('Product #' . $product->id . ' failed to push to Montapackage with error: ' . $e->getMessage());

                return false;
            }
        }
    }

    public static function updateProduct(Product $product): bool
    {
        if (!$product->montaportalProduct) {
            return false;
        }

        try {
            $apiClient = self::initialize();

            try {
                $montaProduct = $apiClient->getProduct($product->montaportalProduct->montaportal_id);
            } catch (Exception $e) {
                try {
                    $montaProduct = $apiClient->getProduct($product->sku);
                } catch (Exception $e) {
                    try {
                        $montaProduct = $apiClient->getProductByBarcode($product->ean);
                    } catch (Exception $e) {
                        $montaProduct = null;
                    }
                }
            }

            if ($montaProduct) {

                $montaProductIsValid = false;
                if ($montaProduct->Sku == $product->Sku) {
                    $montaProductIsValid = true;
                }
                if (in_array($product->ean, $montaProduct->Barcodes)) {
                    $montaProductIsValid = true;
                }
                if (!$montaProductIsValid) {
                    $product->montaportalProduct->delete();
                    $product->refresh();
                    $success = self::createProduct($product);
                    if ($success) {
                        $product->refresh();
                        $success = self::updateProduct($product);
                    }

                    return $success;
                }

                $barcodes = [];
                foreach ($montaProduct->Barcodes as $barcode) {
                    $barcodes[] = $barcode;
                }
                if (!in_array($product->ean, $barcodes)) {
                    $barcodes[] = $product->ean;
                }

                try {
                    $response = $apiClient->updateProduct($product->montaportalProduct->montaportal_id, [
                        'Barcodes' => $barcodes,
                    ]);
                } catch (Exception $e) {
                    $response = null;
                }
                if (!$response) {
                    //                dd($montaProduct, $barcodes);
                }

                foreach ($barcodes as $barcode) {
                    if (!$response) {
                        try {
                            $response = $apiClient->updateProduct($barcode, [
                                'Barcodes' => $barcodes,
                            ]);
                        } catch (Exception $e) {
                        }
                    }
                }

                if (!$response) {
                    try {
                        $response = $apiClient->updateProduct($product->sku, [
                            'Barcodes' => $barcodes,
                        ]);
                    } catch (Exception $e) {
                    }
                }

                if (!($response->Sku ?? false)) {
                } else {
                    $product->montaportalProduct->montaportal_id = $response->Sku;
                    $product->montaportalProduct->save();
                }

                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            if (str($e->getMessage())->contains('Provided SKU does not exist for a single known product')) {
                dump('Deleted product ' . $product->name . ' - ' . $product->sku);
                $product->montaportalProduct->delete();
            }

            dump($e->getMessage(), 'failed updating product');

            return false;
        }
    }

    public static function syncProductStock(Product $product)
    {
        if (!$product->montaportalProduct || !$product->montaportalProduct->sync_stock) {
            return;
        }

        try {
            $apiClient = self::initialize();
            $response = $apiClient->getProductStock($product->montaportalProduct->montaportal_id);
            $stock = $response->Stock->StockAvailable;
            $product->stock = $stock;
            $product->save();
        } catch (Exception $e) {
            $product->stock = 0;
            $product->save();
        }
    }

    public static function deleteProduct(Product $product)
    {
        if (!$product->montaportalProduct) {
            return;
        }

        try {
            $apiClient = self::initialize();
            $response = $apiClient->deleteProduct($product->montaportalProduct->montaportal_id);

            $product->montaportalProduct->delete();
        } catch (Exception $e) {
            Mails::sendNotificationToAdmins('Product #' . $product->id . ' failed to delete product from Montapackage with error: ' . $e->getMessage());
        }
    }

    public static function updateTrackandTrace(MontaportalOrder $montaportalOrder): void
    {
        if ($montaportalOrder->pushed_to_montaportal != 1 || $montaportalOrder->track_and_trace_present) {
            return;
        }

        $trackAndTraceLinks = [];
        $ordersCount = 0;

        try {
            $apiClient = self::initialize();
            $efulfillmentOrder = $apiClient->getOrder($montaportalOrder->order->id);
            if ($efulfillmentOrder->TrackAndTraceLink && $efulfillmentOrder->TrackAndTraceLink != null) {
                $trackAndTraceLinks[] = $efulfillmentOrder->TrackAndTraceLink;
            }
            $ordersCount++;
        } catch (Exception $e) {
            $montaportalOrder->track_and_trace_present = 2;
            $montaportalOrder->save();
        }

        if ($montaportalOrder->montaportal_pre_order_ids) {
            foreach ($montaportalOrder->montaportal_pre_order_ids as $preOrderId) {
                try {
                    $efulfillmentOrder = $apiClient->getOrder($preOrderId);
                    if ($efulfillmentOrder->TrackAndTraceLink && $efulfillmentOrder->TrackAndTraceLink != null) {
                        $trackAndTraceLinks[] = $efulfillmentOrder->TrackAndTraceLink;
                    }
                    $ordersCount++;
                } catch (Exception $e) {
                    $montaportalOrder->track_and_trace_present = 2;
                    $montaportalOrder->save();
                }
            }
        }

        if ($ordersCount == count($trackAndTraceLinks)) {
            $montaportalOrder->order->changeFulfillmentStatus('packed');
        }
        $montaportalOrder->order->save();
        $montaportalOrder->save();

        if ($trackAndTraceLinks) {
            $trackAndTraces = $trackAndTraceLinks;
            if ($montaportalOrder->track_and_trace_links != $trackAndTraces) {
                $montaportalOrder->track_and_trace_links = $trackAndTraces;
                $montaportalOrder->track_and_trace_present = 1;
                $montaportalOrder->save();

                $orderLog = new OrderLog();
                $orderLog->order_id = $montaportalOrder->order->id;
                $orderLog->user_id = null;

                try {
                    Mail::to($montaportalOrder->order->email)->send(new TrackandTraceMail($montaportalOrder));
                    $orderLog->tag = 'order.t&t.send';
                } catch (\Exception $e) {
                    $orderLog->tag = 'order.t&t.not-send';
                }
                $orderLog->save();
            }
        }
    }

    public static function updateOrder(Order $order): void
    {
        if ($order->fulfillment_status == 'handled' || !$order->montaPortalOrder || !$order->montaPortalOrder->montaportal_id) {
            return;
        }

        $allOrdersShipped = true;
        $allOrdersDelivered = true;

        $apiClient = self::initialize();

        try {
            $efulfillmentOrder = $apiClient->getOrder($order->montaPortalOrder->montaportal_id);
        } catch (Exception $e) {
            return;
        }
        if (!$efulfillmentOrder->Shipped) {
            $allOrdersShipped = false;
        }

        if ($efulfillmentOrder->DeliveryStatusCode != 'Delivered') {
            $allOrdersDelivered = false;
        }

        if ($order->montaPortalOrder->montaportal_pre_order_ids) {
            foreach ($order->montaPortalOrder->montaportal_pre_order_ids as $preOrderId) {
                try {
                    $efulfillmentOrder = $apiClient->getOrder($preOrderId);
                } catch (Exception $e) {
                    return;
                }
                if (!$efulfillmentOrder->Shipped) {
                    $allOrdersShipped = false;
                }
                if ($efulfillmentOrder->DeliveryStatusCode != 'Delivered') {
                    $allOrdersDelivered = false;
                }
            }
        }

        if ($allOrdersShipped && !$allOrdersDelivered) {
            $order->changeFulfillmentStatus('shipped');
        } elseif ($allOrdersShipped && $allOrdersDelivered) {
            $order->changeFulfillmentStatus('handled');
        }
    }

    public static function createOrder(MontaportalOrder $montaPortalOrder, bool $debug = false): bool
    {
        if ($debug) {
            dump('Order ID: ' . $montaPortalOrder->order_id);
        }

        if ($montaPortalOrder->pushed_to_montaportal == 1) {
            return false;
        }

        if (MontaportalOrder::where('order_id', $montaPortalOrder->order_id)->where('pushed_to_montaportal', 1)->where('id', '!=', $montaPortalOrder->id)->count()) {
            $montaPortalOrder->delete();

            return false;
        }

        try {
            $apiClient = self::initialize();

            $allProductsPushedToEfulfillment = true;
            $missingProducts = [];
            foreach ($montaPortalOrder->order->orderProductsWithProduct as $orderProduct) {
                if (!$orderProduct->product->is_bundle) {
                    if (!$orderProduct->product->montaportalProduct) {
                        $allProductsPushedToEfulfillment = false;
                        $missingProducts[] = $orderProduct->name . ' (' . $orderProduct->id . ')';
                    }
                }
            }

            if (!$allProductsPushedToEfulfillment) {
                Mails::sendNotificationToAdmins('Order #' . $montaPortalOrder->order->id . ' failed to push to Montaportal because not all products are pushed to Montaportal');
                $montaPortalOrder->pushed_to_montaportal = 2;
                $montaPortalOrder->error = 'Not all products are pushed to Montaportal: ' . implode(', ', $missingProducts);
                $montaPortalOrder->save();

                return false;
            }

            $orderProducts = [];
            $preOrderedOrderedProducts = [];
            $hasAllProductsPushedToMonta = true;

            foreach ($montaPortalOrder->order->orderProductsWithProduct as $orderProduct) {
                if (!$orderProduct->product->is_bundle) {
                    if ($orderProduct->is_pre_order && $orderProduct->pre_order_restocked_date && Carbon::parse($orderProduct->pre_order_restocked_date) > Carbon::now()->endOfDay()) {
                        //                        dd($orderProduct->product);
                        $preOrderedOrderedProducts[] = [
                            'orderProductId' => $orderProduct->id,
                            'Sku' => $orderProduct->product->montaportalProduct->montaportal_id,
                            'OrderedQuantity' => $orderProduct->quantity,
                            'preOrderDate' => Carbon::parse($orderProduct->pre_order_restocked_date)->format('d-m-Y'),
                        ];
                    } else {
                        if (!$orderProduct->product->montaportalProduct) {
                            dump($orderProduct->product->name . ' not pushed to montaportal');
                            $hasAllProductsPushedToMonta = false;
                        } else {
                            $orderProducts[] = [
                                'orderProductId' => $orderProduct->id,
                                'Sku' => $orderProduct->product->montaportalProduct->montaportal_id,
                                'OrderedQuantity' => $orderProduct->quantity,
                            ];
                        }
                    }
                }
            }

            if (!$hasAllProductsPushedToMonta) {
                return false;
            }

            //            $montaPortalOrder->order->createInvoice();

            if ($orderProducts) {
                $data = [
                    'WebshopOrderId' => $montaPortalOrder->order->invoice_id,
                    'ConsumerDetails' => [
                        'DeliveryAddress' => [
                            'LastName' => $montaPortalOrder->order->name,
                            'Street' => $montaPortalOrder->order->street,
                            'HouseNumber' => $montaPortalOrder->order->house_nr,
                            'City' => $montaPortalOrder->order->city,
                            'PostalCode' => $montaPortalOrder->order->zip_code,
//                            'CountryCode' => Countries::getCountryIsoCode($order->country) ?: 'NL',
                            'CountryCode' => $montaPortalOrder->order->country,
                            'EmailAddress' => $montaPortalOrder->order->email,
                        ],
                        'B2b' => false,
                    ],
                    'notes' => 'No notes',
                    'lines' => $orderProducts,
                    'ProformaInvoiceUrl' => null,
//                    'ProformaInvoiceUrl' => env('APP_ENV') == 'local' ? null : $montaPortalOrder->order->downloadInvoiceUrl(),
                ];

                if ($debug) {
                    dump($data);
                }
                $response = $apiClient->addOrder($data);
            }

            if ($preOrderedOrderedProducts) {
                $efulfillmentPreOrderIds = [];
                foreach ($preOrderedOrderedProducts as $preOrderedOrderedProduct) {
                    $orderProducts = [];
                    $preOrderDate = $preOrderedOrderedProduct['preOrderDate'];
                    $orderProducts[] = [
                        'Sku' => $preOrderedOrderedProduct['Sku'],
                        'OrderedQuantity' => $preOrderedOrderedProduct['OrderedQuantity'],
                    ];

                    $orderId = $montaPortalOrder->order->invoice_id . '-pre-order-' . $preOrderedOrderedProduct['Sku'];
                    $efulfillmentPreOrderIds[] = $orderId;
                    $data = [
                        'WebshopOrderId' => $orderId,
                        'ConsumerDetails' => [
                            'DeliveryAddress' => [
                                'LastName' => $montaPortalOrder->order->name,
                                'Street' => $montaPortalOrder->order->street,
                                'HouseNumber' => $montaPortalOrder->order->house_nr,
                                'City' => $montaPortalOrder->order->city,
                                'PostalCode' => $montaPortalOrder->order->zip_code,
                                'CountryCode' => $montaPortalOrder->order->country,
                                'EmailAddress' => $montaPortalOrder->order->email,
                            ],
                            'B2b' => false,
                        ],
                        'PlannedShipmentDate' => Carbon::parse($preOrderDate),
                        'ShipOnPlannedShipmentDate' => true,
                        'notes' => 'No notes',
                        'lines' => $orderProducts,
                        'ProformaInvoiceUrl' => null,
//                        'ProformaInvoiceUrl' => env('APP_ENV') == 'local' ? null : $montaPortalOrder->order->downloadInvoiceUrl(),
                    ];

                    if ($debug) {
                        dump($data);
                    }

                    $response = $apiClient->addOrder($data);
                }
                $montaPortalOrder->montaportal_pre_order_ids = $efulfillmentPreOrderIds;
                $montaPortalOrder->save();
            }


            if (isset($response)) {
                if ($response->WebshopOrderId) {
                    $montaPortalOrder->error = '';
                    $montaPortalOrder->pushed_to_montaportal = 1;
                    $montaPortalOrder->montaportal_id = $response->WebshopOrderId;
                    $montaPortalOrder->save();

                    $montaPortalOrder->order->changeFulfillmentStatus('in_treatment');

                    foreach ($orderProducts as $orderProduct) {
                        OrderProduct::whereId($orderProduct['orderProductId'])->update([
                            'send_to_fulfiller' => true,
                        ]);
                    }

                    $orderLog = new OrderLog();
                    $orderLog->order_id = $montaPortalOrder->order->id;
                    $orderLog->user_id = Auth::user()->id ?? null;
                    $orderLog->tag = 'order.pushed-to-montaportal';
                    $orderLog->save();
                } else {
                    if ($montaPortalOrder->pushed_to_montaportal != 2) {
                        Mails::sendNotificationToAdmins('Order #' . $montaPortalOrder->order->id . ' failed to push to Montaportal');
                        $montaPortalOrder->error = $response['error'] ?? serialize($response);
                        $montaPortalOrder->pushed_to_montaportal = 2;
                        $montaPortalOrder->save();

                        if (str($montaPortalOrder->error)->contains('An order with that Webshop Order ID already exists')) {
                            $montaPortalOrder->error = '';
                            $montaPortalOrder->pushed_to_montaportal = 1;
                            $montaPortalOrder->save();
                        }
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            dump($e->getMessage());
            if ($montaPortalOrder->pushed_to_montaportal != 2) {
                Mails::sendNotificationToAdmins('Order #' . $montaPortalOrder->order->id . ' failed to push to Montaportal with error: ' . $e->getMessage());
                $montaPortalOrder->pushed_to_montaportal = 2;
                $montaPortalOrder->save();
            }
            $montaPortalOrder->error = $e->getMessage();
            $montaPortalOrder->save();

            if (str($montaPortalOrder->error)->contains('An order with that Webshop Order ID already exists')) {
                $montaPortalOrder->error = '';
                $montaPortalOrder->pushed_to_montaportal = 1;
                $montaPortalOrder->save();

                return true;
            }

            return false;
        }
    }

    public static function validateAddress(string $street, string $housenumber, string $postalCode, string $city, string $country): array
    {
        try {
            $response = Http::withBasicAuth(Customsetting::get('montaportal_username'), Customsetting::get('montaportal_password'))
                ->post('https://api-v6.monta.nl/address', [
                    'LastName' => $street,
                    'Street' => $street,
                    'HouseNumber' => $housenumber,
                    'City' => $city,
                    'PostalCode' => $postalCode,
                    'CountryCode' => $country,
                ])
                ->json();

            if (count($response['OrderInvalidReasons'] ?? [])) {
                return [
                    'success' => false,
                    'message' => Translation::get(str($response['OrderInvalidReasons'][0]['Message'])->slug(), 'montaportal', $response['OrderInvalidReasons'][0]['Message']),
                ];
            }

            return [
                'success' => true,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => Translation::get('wrong-address-found', 'montaportal', 'Address validation failed'),
            ];
        }
    }
}
