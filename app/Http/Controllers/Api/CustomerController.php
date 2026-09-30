<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Customer;
use Illuminate\Http\Request;
use App\Custom\GetAdquirerRequest;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Customer::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return Customer::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        return $customer;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->all());
        return $customer;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado'
        ], 200);
    }

    public function getAcquirer($document_type_identification_id, $document_number)
    {
        try {
            \Log::info('GetAcquirer request', [
                'document_type' => $document_type_identification_id,
                'document_number' => $document_number
            ]);

            $response = $this->createXML($document_type_identification_id, $document_number);

            \Log::info('GetAcquirer response', ['response' => json_encode($response)]);

            $status = $response->Envelope->Body->GetAcquirerResponse->GetAcquirerResult->StatusCode ?? null;
            $message = $response->Envelope->Body->GetAcquirerResponse->GetAcquirerResult->Message ?? '';

            if($status === '404') {
                return [
                    'success' => false,
                    'message' => $message,
                    'status' => $status
                ];
            }

            return [
                'success' => true,
                'message' => $message,
                'ResponseDian' => $response->Envelope->Body,
                'status' => $status
            ];
        } catch (\Exception $e) {
            \Log::error('GetAcquirer error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    protected function createXML($document_type_identification_id, $document_number)
    {
        $company = auth()->user()->company;
        $getAdquirerRequest = new GetAdquirerRequest($company->certificate->path, $company->certificate->password);
        $getAdquirerRequest->identificationType = $document_type_identification_id;
        $getAdquirerRequest->identificationNumber = $document_number;
        $getAdquirerRequest->To = $company->software->url;
        $respuestadian = $getAdquirerRequest->signToSend()->getResponseToObject();

        return $respuestadian;
    }
}
