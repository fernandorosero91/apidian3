<cac:PaymentTerms>
    <cbc:ReferenceEventCode>{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->code)}}</cbc:ReferenceEventCode>
        <cac:SettlementPeriod>
            @if(isset($paymentForm->duration_measure))
                <cbc:DurationMeasure unitCode="{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->duration_measure_unit_code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->duration_measure)}}</cbc:DurationMeasure>
            @else
                <cbc:DurationMeasure unitCode="{{preg_replace("/[\r\n|\n|\r]+/", "", $paymentForm->duration_measure_unit_code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", "0")}}</cbc:DurationMeasure>
            @endif
        </cac:SettlementPeriod>
</cac:PaymentTerms>
