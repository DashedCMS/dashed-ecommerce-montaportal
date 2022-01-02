<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceCore\Models\Translation;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalOrder;

class TrackandTraceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(MontaportalOrder $montaPortalOrder)
    {
        $this->montaPortalOrder = $montaPortalOrder;
        $this->order = $montaPortalOrder->order;
    }

    public function build()
    {
        return $this->view('qcommerce-ecommerce-montaportal::emails.track-and-trace')->from(Customsetting::get('site_from_email'), Customsetting::get('company_name'))->subject(Translation::get('order-montaportal-track-and-trace-email-subject', 'montaportal', 'Your order #:orderId: has been updated', 'text', [
            'orderId' => $this->order->invoice_id,
        ]))->with([
            'montaPortalOrder' => $this->montaPortalOrder,
            'order' => $this->order,
        ]);
    }
}
