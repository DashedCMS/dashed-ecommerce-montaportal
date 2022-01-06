<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Classes;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Qubiqx\Montapacking\Client;
use Qubiqx\QcommerceCore\Classes\Mails;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceCore\Models\OrderLog;
use Qubiqx\QcommerceEcommerceCore\Models\Product;
use Qubiqx\QcommerceEcommerceMontaportal\Mail\TrackandTraceMail;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalOrder;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalProduct;

class Montaportal
{
    public static function isConnected($siteId = null)
    {
        if (! $siteId) {
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
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        return new Client(Customsetting::get('montaportal_username', $siteId), Customsetting::get('montaportal_password', $siteId));
    }

    public static function createProduct(Product $product)
    {
        if (! $product->ean) {
            return false;
        }

        if ($product->montaportalProduct) {
            return true;
        }

        try {
            $apiClient = self::initialize();
            $response = $apiClient->addProduct([
                'Sku' => $product->sku,
                'Description' => $product->name,
                'Barcodes' => [$product->ean],
            ]);

            if (! $response->Sku) {
                Mails::sendNotificationToAdmins('Product #' . $product->id . ' failed to push to Montapackage');
            } else {
                $montaportalProduct = new MontaportalProduct();
                $montaportalProduct->product_id = $product->id;
                $montaportalProduct->montaportal_id = $response->Sku;
                $montaportalProduct->save();
            }

            return true;
        } catch (Exception $e) {
            Mails::sendNotificationToAdmins('Product #' . $product->id . ' failed to push to Montapackage with error: ' . $e->getMessage());

            return false;
        }
    }

    public static function updateProduct(Product $product)
    {
        if (! $product->montaPortalProduct) {
            return;
        }

        try {
            $apiClient = self::initialize();
            $montaProduct = $apiClient->getProduct($product->montaPortalProduct->montaportal_id);
            $barcodes = [];
            foreach ($montaProduct->Barcodes as $barcode) {
                $barcodes[] = $barcode;
            }
            if (! in_array($product->ean, $barcodes)) {
                $barcodes[] = $product->ean;
            }

            $response = $apiClient->updateProduct($product->sku, [
                'Barcodes' => $barcodes,
            ]);

            if (! $response->Sku) {
            } else {
                $product->montaPortalProduct->montaportal_id = $response->Sku;
                $product->montaPortalProduct->save();
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function syncProductStock(Product $product)
    {
        if (! $product->montaPortalProduct || ! $product->montaPortalProduct->sync_stock) {
            return;
        }

        try {
            $apiClient = self::initialize();
            $response = $apiClient->getProductStock($product->montaPortalProduct->montaportal_id);
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
        if (! $product->montaPortalProduct) {
            return;
        }

        try {
            $apiClient = self::initialize();
            $response = $apiClient->deleteProduct($product->montaPortalProduct->montaportal_id);

            $product->montaPortalProduct->delete();
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
                    $apiClient = self::initialize();
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

    public static function createOrder(MontaportalOrder $montaPortalOrder)
    {
        if ($montaPortalOrder->pushed_to_montaportal == 1) {
            return;
        }

        try {
            $apiClient = self::initialize();

            $allProductsPushedToEfulfillment = true;
            foreach ($montaPortalOrder->order->orderProductsWithProduct as $orderProduct) {
                if (! $orderProduct->product->montaPortalProduct) {
                    $allProductsPushedToEfulfillment = false;
                }
            }

            if (! $allProductsPushedToEfulfillment && $montaPortalOrder->order->montaPortalOrder->pushed_to_montaportal != 2) {
                Mails::sendNotificationToAdmins('Order #' . $montaPortalOrder->order->id . ' failed to push to Montaportal because not all products are pushed to Montaportal');
                $montaPortalOrder->pushed_to_montaportal = 2;
                $montaPortalOrder->save();
            }

            $orderedProducts = [];
            $preOrderedOrderedProducts = [];

            foreach ($montaPortalOrder->order->orderProductsWithProduct as $orderProduct) {
                if ($orderProduct->is_pre_order && $orderProduct->pre_order_restocked_date && Carbon::parse($orderProduct->pre_order_date) > Carbon::now()->endOfDay()) {
                    $preOrderedOrderedProducts[] = [
                        'Sku' => $orderProduct->product->montaPortalProduct->montaportal_id,
                        'OrderedQuantity' => $orderProduct->quantity,
                        'preOrderDate' => Carbon::parse($orderProduct->pre_order_restocked_date)->format('d-m-Y'),
                    ];
                } else {
                    $orderedProducts[] = [
                        'Sku' => $orderProduct->product->montaPortalProduct->montaportal_id,
                        'OrderedQuantity' => $orderProduct->quantity,
                    ];
                }
            }

            $montaPortalOrder->order->createInvoice();

            if ($orderedProducts) {
                $data = [
                    'WebshopOrderId' => $montaPortalOrder->order->id,
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
                    'lines' => $orderedProducts,
                    'ProformaInvoiceUrl' => env('APP_ENV') == 'local' ? null : $montaPortalOrder->order->downloadInvoiceUrl(),
                ];

                $response = $apiClient->addOrder($data);
            }

            if ($preOrderedOrderedProducts) {
                $efulfillmentPreOrderIds = [];
                foreach ($preOrderedOrderedProducts as $preOrderedOrderedProduct) {
                    $orderedProducts = [];
                    $preOrderDate = $preOrderedOrderedProduct['preOrderDate'];
                    $orderedProducts[] = [
                        'Sku' => $preOrderedOrderedProduct['Sku'],
                        'OrderedQuantity' => $preOrderedOrderedProduct['OrderedQuantity'],
                    ];

                    $orderId = $montaPortalOrder->order->id . '-pre-order-' . $preOrderedOrderedProduct['Sku'];
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
                        'lines' => $orderedProducts,
                        'ProformaInvoiceUrl' => env('APP_ENV') == 'local' ? null : $montaPortalOrder->order->downloadInvoiceUrl(),
                    ];

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

                    $orderLog = new OrderLog();
                    $orderLog->order_id = $montaPortalOrder->order->id;
                    $orderLog->user_id = Auth::user()->id ?? null;
                    $orderLog->tag = 'order.pushed-to-montaportal';
                    $orderLog->save();
                } else {
                    dd($response);
                    if ($montaPortalOrder->pushed_to_montaportal != 2) {
                        Mails::sendNotificationToAdmins('Order #' . $montaPortalOrder->order->id . ' failed to push to Montaportal');
                        $montaPortalOrder->error = $response['error'] ?? serialize($response);
                        $montaPortalOrder->pushed_to_montaportal = 2;
                        $montaPortalOrder->save();
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            dd($e->getMessage());
            if ($montaPortalOrder->pushed_to_montaportal != 2) {
                Mails::sendNotificationToAdmins('Order #' . $montaPortalOrder->order->id . ' failed to push to Montaportal with error: ' . $e->getMessage());
                $montaPortalOrder->error = $e->getMessage();
                $montaPortalOrder->pushed_to_montaportal = 2;
                $montaPortalOrder->save();
            }

            return false;
        }
    }

    //Todo: only implement if needed
//    public static function checkAddress($street, $housenumber, $city, $postalCode, $sendLocation)
//    {
//        try {
//            $response = self::sendRequest('/address', [
//                'LastName' => $street,
//                'Street' => $street,
//                'HouseNumber' => $housenumber,
//                'City' => $city,
//                'PostalCode' => $postalCode,
//                'CountryCode' => $sendLocation->country_short,
//            ], 'POST');
//
//            return true;
//        } catch (Exception $e) {
//            return false;
//        }
//    }
}
