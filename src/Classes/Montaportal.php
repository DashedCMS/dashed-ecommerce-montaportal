<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Classes;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Qubiqx\Montapacking\Client;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceCore\Models\Product;

class Montaportal
{
    public static function connected($siteId = null)
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
//        try {
//            $apiClient = self::initialize();
//            $apiClient->deleteProduct($product->sku);
//        } catch (Exception $e){
//
//        }
//        return true;
        if ($product->ean) {
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
                    try {
                        $notificationInvoiceEmails = Customsetting::get('notification_invoice_emails', Sites::getActive(), '[]');
                        if ($notificationInvoiceEmails) {
                            foreach (json_decode($notificationInvoiceEmails) as $notificationInvoiceEmail) {
                                Mail::to($notificationInvoiceEmail)->send(new NotificationMail('Product #' . $product->id . ' failed to push to Montapackage', 'Product #' . $product->id . ' failed to push to Montapackage'));
                            }
                        }
                    } catch (\Exception $e) {
                    }
                } else {
                    $montaportalProduct = new MontaportalProduct();
                    $montaportalProduct->product_id = $product->id;
                    $montaportalProduct->montaportal_id = $response->Sku;
                    $montaportalProduct->save();
                }

                return true;
            } catch (Exception $firstE) {
                try {
                    $notificationInvoiceEmails = Customsetting::get('notification_invoice_emails', Sites::getActive(), '[]');
                    if ($notificationInvoiceEmails) {
                        foreach (json_decode($notificationInvoiceEmails) as $notificationInvoiceEmail) {
                            Mail::to($notificationInvoiceEmail)->send(new NotificationMail('Product #' . $product->id . ' failed to push to Montapackage with error: ' . $e->getMessage(), 'Product #' . $product->id . ' failed to push to Montapackage with error: ' . $e->getMessage()));
                        }
                    }
                } catch (\Exception $e) {
                }

                return false;
            }
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
            try {
                $notificationInvoiceEmails = Customsetting::get('notification_invoice_emails', Sites::getActive(), '[]');
                if ($notificationInvoiceEmails) {
                    foreach (json_decode($notificationInvoiceEmails) as $notificationInvoiceEmail) {
                        Mail::to($notificationInvoiceEmail)->send(new NotificationMail('Product #' . $product->id . ' failed to delete product from Montapackage with error: ' . $e->getMessage(), 'Product #' . $product->id . ' failed to delete product from Montapackage with error: ' . $e->getMessage()));
                    }
                }
            } catch (\Exception $e) {
            }
        }
    }

    public static function updateTrackandTrace(Order $order): void
    {
        if (! $order->montaPortalOrder || $order->montaPortalOrder->pushed_to_montaportal != 1 || $order->montaPortalOrder->track_and_trace_present) {
            return;
        }

        $trackAndTraceLinks = [];
        $ordersCount = 0;

        try {
            $apiClient = self::initialize();
            $efulfillmentOrder = $apiClient->getOrder($order->id);
            if ($efulfillmentOrder->TrackAndTraceLink && $efulfillmentOrder->TrackAndTraceLink != null) {
                $trackAndTraceLinks[] = $efulfillmentOrder->TrackAndTraceLink;
            }
            $ordersCount++;
        } catch (Exception $e) {
            $order->montaPortalOrder->track_and_trace_present = 2;
            $order->montaPortalOrder->save();
        }

        if ($order->montaPortalOrder->montaportal_pre_order_ids) {
            foreach (json_decode($order->montaPortalOrder->montaportal_pre_order_ids, true) as $preOrderId) {
                try {
                    $apiClient = self::initialize();
                    $efulfillmentOrder = $apiClient->getOrder($preOrderId);
                    if ($efulfillmentOrder->TrackAndTraceLink && $efulfillmentOrder->TrackAndTraceLink != null) {
                        $trackAndTraceLinks[] = $efulfillmentOrder->TrackAndTraceLink;
                    }
                    $ordersCount++;
                } catch (Exception $e) {
                    $order->montaPortalOrder->track_and_trace_present = 2;
                    $order->montaPortalOrder->save();
                }
            }
        }

        if ($ordersCount == count($trackAndTraceLinks)) {
            $order->changeFulfillmentStatus('packed');
        }
        $order->save();

        if ($trackAndTraceLinks) {
            $trackAndTraces = json_encode($trackAndTraceLinks);
            if ($order->montaPortalOrder->track_and_trace_links != $trackAndTraces) {
                $order->montaPortalOrder->track_and_trace_links = $trackAndTraces;
                $order->montaPortalOrder->track_and_trace_present = 1;
                $order->montaPortalOrder->save();

                try {
                    Mail::to($order->email)->send(new OrderMontaportalTrackandTraceMail($order));

                    $orderLog = new OrderLog();
                    $orderLog->order_id = $order->id;
                    $orderLog->user_id = null;
                    $orderLog->tag = 'order.t&t.send';
                    $orderLog->save();
                } catch (\Exception $e) {
                    $orderLog = new OrderLog();
                    $orderLog->order_id = $order->id;
                    $orderLog->user_id = null;
                    $orderLog->tag = 'order.t&t.not-send';
                    $orderLog->save();
                }
            }
        }
    }

    public static function createOrder(Order $order)
    {
        if (! $order->montaPortalOrder || $order->montaPortalOrder->pushed_to_montaportal == 1) {
            return;
        }

        try {
            $apiClient = self::initialize();

            $allProductsPushedToEfulfillment = true;
            foreach ($order->orderProductsWithProduct as $orderProduct) {
                if (! $orderProduct->product->montaPortalProduct) {
                    $allProductsPushedToEfulfillment = false;
                }
            }

            if (! $allProductsPushedToEfulfillment && $order->montaPortalOrder->pushed_to_montaportal != 2) {
                try {
                    $notificationInvoiceEmails = Customsetting::get('notification_invoice_emails', Sites::getActive(), '[]');
                    if ($notificationInvoiceEmails) {
                        foreach (json_decode($notificationInvoiceEmails) as $notificationInvoiceEmail) {
                            Mail::to($notificationInvoiceEmail)->send(new NotificationMail('Order #' . $order->id . ' failed to push to Montaportal because not all products are pushed to Montaportal', 'Order #' . $order->id . ' failed to push to Montaportal because not all products are pushed to Montaportal'));
                        }
                    }
                } catch (\Exception $e) {
                }
                $order->montaPortalOrder->pushed_to_montaportal = 2;
                $order->montaPortalOrder->save();
            }

            $orderedProducts = [];
            $preOrderedOrderedProducts = [];

            foreach ($order->orderProductsWithProduct as $orderProduct) {
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

            $order->createInvoice();

            if ($orderedProducts) {
                $data = [
                    'WebshopOrderId' => $order->id,
                    'ConsumerDetails' => [
                        'DeliveryAddress' => [
                            'LastName' => $order->name,
                            'Street' => $order->street,
                            'HouseNumber' => $order->house_nr,
                            'City' => $order->city,
                            'PostalCode' => $order->zip_code,
//                            'CountryCode' => Countries::getCountryIsoCode($order->country) ?: 'NL',
                            'CountryCode' => $order->country,
                            'EmailAddress' => $order->email,
                        ],
                        'B2b' => false,
                    ],
                    'notes' => 'No notes',
                    'lines' => $orderedProducts,
                    'ProformaInvoiceUrl' => env('APP_ENV') == 'local' ? null : $order->downloadInvoiceUrl(),
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

                    $orderId = $order->id . '-pre-order-' . $preOrderedOrderedProduct['Sku'];
                    $efulfillmentPreOrderIds[] = $orderId;
                    $data = [
                        'WebshopOrderId' => $orderId,
                        'ConsumerDetails' => [
                            'DeliveryAddress' => [
                                'LastName' => $order->name,
                                'Street' => $order->street,
                                'HouseNumber' => $order->house_nr,
                                'City' => $order->city,
                                'PostalCode' => $order->zip_code,
                                'CountryCode' => $order->sendLocation->country_short,
                                'EmailAddress' => $order->email,
                            ],
                            'B2b' => false,
                        ],
                        'PlannedShipmentDate' => Carbon::parse($preOrderDate),
                        'ShipOnPlannedShipmentDate' => true,
                        'notes' => 'No notes',
                        'lines' => $orderedProducts,
                        'ProformaInvoiceUrl' => env('APP_ENV') == 'local' ? null : $order->downloadInvoiceUrl(),
                    ];

                    $response = $apiClient->addOrder($data);
                }
                $order->montaPortalOrder->montaportal_pre_order_ids = $efulfillmentPreOrderIds;
                $order->montaPortalOrder->save();
            }

            if (isset($response)) {
                if ($response->WebshopOrderId) {
                    $order->montaPortalOrder->error = '';
                    $order->montaPortalOrder->pushed_to_montaportal = 1;
                    $order->montaPortalOrder->montaportal_id = $response->WebshopOrderId;
                    $order->montaPortalOrder->save();

                    $order->changeFulfillmentStatus('in_treatment');

                    $orderLog = new OrderLog();
                    $orderLog->order_id = $order->id;
                    $orderLog->user_id = Auth::user()->id ?? null;
                    $orderLog->tag = 'order.pushed-to-montaportal';
                    $orderLog->save();
                } else {
                    if ($order->montaPortalOrder->pushed_to_montaportal != 2) {
                        try {
                            $notificationInvoiceEmails = Customsetting::get('notification_invoice_emails', Sites::getActive(), '[]');
                            if ($notificationInvoiceEmails) {
                                foreach (json_decode($notificationInvoiceEmails) as $notificationInvoiceEmail) {
                                    Mail::to($notificationInvoiceEmail)->send(new NotificationMail('Order #' . $order->id . ' failed to push to Montaportal', 'Order #' . $order->id . ' failed to push to Montaportal'));
                                }
                            }
                        } catch (\Exception $e) {
                        }
                        $order->montaPortalOrder->error = $response['error'] ?? serialize($response);
                        $order->montaPortalOrder->pushed_to_montaportal = 2;
                        $order->montaPortalOrder->save();
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            if ($order->montaPortalOrder->pushed_to_montaportal != 2) {
                try {
                    $notificationInvoiceEmails = Customsetting::get('notification_invoice_emails', Sites::getActive(), '[]');
                    if ($notificationInvoiceEmails) {
                        foreach (json_decode($notificationInvoiceEmails) as $notificationInvoiceEmail) {
                            Mail::to($notificationInvoiceEmail)->send(new NotificationMail('Order #' . $order->id . ' failed to push to Montaportal with error: ' . $e->getMessage(), 'Order #' . $order->id . ' failed to push to Montaportal with error: ' . $e->getMessage()));
                        }
                    }
                } catch (\Exception $e) {
                }
                $order->montaPortalOrder->error = $e->getMessage();
                $order->montaPortalOrder->pushed_to_montaportal = 2;
                $order->montaPortalOrder->save();
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
