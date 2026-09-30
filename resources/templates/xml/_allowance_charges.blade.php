@foreach ($allowanceCharges as $key => $allowanceCharge)
    <cac:AllowanceCharge>
        <cbc:ID>{{preg_replace("/[\r\n|\n|\r]+/", "", ($key + 1))}}</cbc:ID>
        <cbc:ChargeIndicator>{{preg_replace("/[\r\n|\n|\r]+/", "", $allowanceCharge->charge_indicator)}}</cbc:ChargeIndicator>
        @if (($allowanceCharge->charge_indicator === 'false') && ($allowanceCharge->discount))
            <cbc:AllowanceChargeReasonCode>{{preg_replace("/[\r\n|\n|\r]+/", "", $allowanceCharge->discount->code)}}</cbc:AllowanceChargeReasonCode>
        @endif
        <cbc:AllowanceChargeReason>{{preg_replace("/[\r\n|\n|\r]+/", "", $allowanceCharge->allowance_charge_reason)}}</cbc:AllowanceChargeReason>
        <cbc:MultiplierFactorNumeric>{{preg_replace("/[\r\n|\n|\r]+/", "", $allowanceCharge->multiplier_factor_numeric)}}</cbc:MultiplierFactorNumeric>
{{--        @if(isset($idcurrency))
            <cbc:Amount currencyID="{{preg_replace("/[\r\n|\n|\r]+/", "", $idcurrency->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", number_format($allowanceCharge->amount, 2, '.', ''))}}</cbc:Amount>
        @else   --}}
            <cbc:Amount currencyID="{{preg_replace("/[\r\n|\n|\r]+/", "", $company->type_currency->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", number_format($allowanceCharge->amount, 2, '.', ''))}}</cbc:Amount>
{{--        @endif  --}}
        @if ($allowanceCharge->base_amount)
{{--            @if(isset($idcurrency))
                <cbc:BaseAmount currencyID="{{preg_replace("/[\r\n|\n|\r]+/", "", $idcurrency->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", number_format($allowanceCharge->base_amount, 2, '.', ''))}}</cbc:BaseAmount>
            @else   --}}
                <cbc:BaseAmount currencyID="{{preg_replace("/[\r\n|\n|\r]+/", "", $company->type_currency->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", number_format($allowanceCharge->base_amount, 2, '.', ''))}}</cbc:BaseAmount>
{{--            @endif  --}}
        @endif
    </cac:AllowanceCharge>
@endforeach
