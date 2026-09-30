@foreach ($healthUsers as $key => $healthUser)
    <Collection schemeName="Usuario">
        <AdditionalInformation>
            <Name>CODIGO_PRESTADOR</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->provider_code)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>MODALIDAD_PAGO</Name>
            <Value schemeName="salud_modalidad_pago.gc" schemeID="{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_contracting_payment_method()->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_contracting_payment_method()->name)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>TIPO_DOCUMENTO_IDENTIFICACION</Name>
            <Value schemeName="salud_identificacion.gc" schemeID="{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_type_document_identification()->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_type_document_identification()->name)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>NUMERO_DOCUMENTO_IDENTIFICACION</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->identification_number)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>PRIMER_APELLIDO</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->surname)}}</Value>
        </AdditionalInformation>
        @isset($healthUser->second_surname)
            <AdditionalInformation>
                <Name>SEGUNDO_APELLIDO</Name>
                <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->second_surname)}}</Value>
            </AdditionalInformation>
        @endisset
        <AdditionalInformation>
            <Name>PRIMER_NOMBRE</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->first_name)}}</Value>
        </AdditionalInformation>
        @isset($healthUser->middle_name)
            <AdditionalInformation>
                <Name>SEGUNDO_NOMBRE</Name>
                <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->middle_name)}}</Value>
            </AdditionalInformation>
        @endisset
        <AdditionalInformation>
            <Name>TIPO_USUARIO</Name>
            <Value schemeName="salud_tipo_usuario.gc" schemeID="{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_type_user()->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_type_user()->name)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>COBERTURA_PLAN_BENEFICIOS</Name>
            <Value schemeName="salud_cobertura.gc" schemeID="{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_coverage()->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->health_coverage()->name)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>NUMERO_AUTORIZACIÓN</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->autorization_numbers)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>NUMERO_MIPRES</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->mipres)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>NUMERO_ENTREGA_MIPRES</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->mipres_delivery)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>NUMERO_CONTRATO</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->contract_number)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>NUMERO_POLIZA</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->policy_number)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>COPAGO</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->co_payment)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>CUOTA_MODERADORA</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->moderating_fee)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>CUOTA_RECUPERACION</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->recovery_fee)}}</Value>
        </AdditionalInformation>
        <AdditionalInformation>
            <Name>PAGOS_COMPARTIDOS</Name>
            <Value>{{preg_replace("/[\r\n|\n|\r]+/", "", $healthUser->shared_payment)}}</Value>
        </AdditionalInformation>
    </Collection>
@endforeach
