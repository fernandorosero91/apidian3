<ext:UBLExtensions>
    <?php $id_extension = 0; ?>
    <ext:UBLExtension>
        <ext:ExtensionContent>
            <sts:DianExtensions>
                <sts:InvoiceSource>
                    <cbc:IdentificationCode listAgencyID="6" listAgencyName="United Nations Economic Commission for Europe" listSchemeURI="urn:oasis:names:specification:ubl:codelist:gc:CountryIdentificationCode-2.1">{{preg_replace("/[\r\n|\n|\r]+/", "", $company->country->code)}}</cbc:IdentificationCode>
                </sts:InvoiceSource>
                <sts:SoftwareProvider>
                    <sts:ProviderID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" @if (preg_replace("/[\r\n|\n|\r]+/", "", $company->type_document_identification_id) === '6' || preg_replace("/[\r\n|\n|\r]+/", "", $company->type_document_identification_id) === '3') schemeID="{{preg_replace("/[\r\n|\n|\r]+/", "", $company->dv)}}" @endif @if (preg_replace("/[\r\n|\n|\r]+/", "", $company->type_document_identification_id) === '6' || preg_replace("/[\r\n|\n|\r]+/", "", $company->type_document_identification_id) === '3') schemeName="31" @else schemeName="{{preg_replace("/[\r\n|\n|\r]+/", "", $company->type_document_identification->code)}}" @endif >{{preg_replace("/[\r\n|\n|\r]+/", "", $company->identification_number)}}</sts:ProviderID>
                    <sts:SoftwareID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)">{{preg_replace("/[\r\n|\n|\r]+/", "", $company->software->identifier_eqdocs)}}</sts:SoftwareID>
                </sts:SoftwareProvider>
                <sts:SoftwareSecurityCode schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)"/>
                <sts:AuthorizationProvider>
                    <sts:AuthorizationProviderID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeID="4" schemeName="31">800197268</sts:AuthorizationProviderID>
                </sts:AuthorizationProvider>
                @if(isset($QRCode))
                    <sts:QRCode>{{$QRCode}}</sts:QRCode>
                @endif
            </sts:DianExtensions>
        </ext:ExtensionContent>
    </ext:UBLExtension>
    @if(isset($request['software_manufacturer']))
        <?php $id_extension++; ?>
        <ext:UBLExtension>
            <cbc:ID>{{$id_extension}}</cbc:ID>
            <cbc:Name>FabricanteSoftware</cbc:Name>
            <ext:ExtensionContent>
                <FabricanteSoftware>
                    <InformacionDelFabricanteDelSoftware>
                        <Name>NombreApellido</Name>
                        <Value>{{$request['software_manufacturer']['name']}}</Value>
                        <Name>RazonSocial</Name>
                        <Value>{{$request['software_manufacturer']['business_name']}}</Value>
                        <Name>NombreSoftware</Name>
                        <Value>{{$request['software_manufacturer']['software_name']}}</Value>
                    </InformacionDelFabricanteDelSoftware>
                </FabricanteSoftware>
            </ext:ExtensionContent>
        </ext:UBLExtension>
    @endif
    @if(isset($request['spd']['residuos']))
        <?php $id_extension++; ?>
        <ext:UBLExtension>
            <cbc:ID schemeID="SPD">{{$id_extension}}</cbc:ID>
            <cbc:Name>Residuos</cbc:Name>
            <ext:ExtensionAgencyName>{{$user->name}}</ext:ExtensionAgencyName>
            <ext:ExtensionReason>{{$request['spd']['residuos']['office_lending_company']}}</ext:ExtensionReason>
            <ext:ExtensionContent>
				<Services_SPD>
					<ID schemeName="Contrato">{{$request['spd']['residuos']['contract_number']}}</ID>
					<IssueDate>{{$request['spd']['residuos']['issue_date']}}</IssueDate>
					<Note>{{$request['spd']['residuos']['note']}}</Note>
					<SenderParty>
                        <cac:PartyIdentification>
                            <ID schemeID="{{preg_replace("/[\r\n|\n|\r]+/", "", $user->company->dv)}}" schemeName="{{preg_replace("/[\r\n|\n|\r]+/", "", $user->company->type_document_identification->code)}}">{{preg_replace("/[\r\n|\n|\r]+/", "", $user->company->identification_number)}}</ID>
                        </cac:PartyIdentification>
                        <PartyName>
                            <Name>{{preg_replace("/[\r\n|\n|\r]+/", "", $user->name)}}</Name>
                        </PartyName>
					</SenderParty>
					<SubscriberConsumption>
						<DurationOfTheBillingCycle unitOfTime="mes">{{$request['spd']['residuos']['duration_of_the_billing_cycle']}}</DurationOfTheBillingCycle>
						<Note>{{$request['spd']['residuos']['consumption_section_note']}}</Note>
						<ConsumptionSection>
							<SPDDebitsForPartialConsumption>
								<SPDDebitForPartialConsumption>
                                    @inject('um', 'App\UnitMeasure')
									<TotalMeteredQuantity unitCode="{{preg_replace("/[\r\n|\n|\r]+/", "", $um->findOrFail($request['spd']['residuos']['total_metered_unit_id'])['code'])}}">{{$request['spd']['residuos']['total_metered_quantity']}}"</TotalMeteredQuantity>
									<ConsumptionPayableAmount currencyID="{{preg_replace("/[\r\n|\n|\r]+/", "", $company->type_currency->code)}}">{{$request['spd']['residuos']['consumption_payable_amount']}}</ConsumptionPayableAmount>
									<Consumptionprice>
										<Quantity unitCode="{{preg_replace("/[\r\n|\n|\r]+/", "", $um->findOrFail($request['spd']['residuos']['total_metered_unit_id'])['code'])}}">{{$request['spd']['residuos']['consumption_price_quantity']}}</Quantity>
										<PartialLineExtensionAmount currencyID="{{preg_replace("/[\r\n|\n|\r]+/", "", $company->type_currency->code)}}">{{$request['spd']['residuos']['partial_line_extension_amount']}}</PartialLineExtensionAmount>
									</Consumptionprice>
									<CargosDebitoAlItem>
										<CargoDebitoAlItem>
											<ID>_identificador_</ID>
											<ChargeReason>Cargo fijo</ChargeReason>
											<Amount>3167.55</Amount>
										</CargoDebitoAlItem>
									</CargosDebitoAlItem>
                                </SPDDebitForPartialConsumption>
                            </SPDDebitsForPartialConsumption>
                        </ConsumptionSection>
                    </SubscriberConsumption>
                </Services_SPD>
            </ext:ExtensionContent>
        </ext:UBLExtension>
    @endif
    <ext:UBLExtension>
        <ext:ExtensionContent/>
    </ext:UBLExtension>
</ext:UBLExtensions>
