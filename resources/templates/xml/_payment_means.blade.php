<cac:PaymentMeans>
    <cbc:ID>{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->code)}}</cbc:ID>
    <cbc:PaymentMeansCode>{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->payment_method_code)}}</cbc:PaymentMeansCode>
    @if(isset($paymentForm->payment_due_date))
        <cbc:PaymentDueDate>{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->payment_due_date)}}</cbc:PaymentDueDate>
    @else
        <cbc:PaymentDueDate>{{preg_replace("/[\r\n|\n|\r]+/", "", $date ?? Carbon\Carbon::now()->format('Y-m-d'))}}</cbc:PaymentDueDate>
    @endif
    <cbc:PaymentID>{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->payment_id)}}</cbc:PaymentID>
</cac:PaymentMeans>
