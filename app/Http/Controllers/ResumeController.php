<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Company;
use App\Document;
use Illuminate\Support\Facades\DB;

class ResumeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }


    public function index()
    {
    }

    public function information($nit, $desde = NULL, $hasta = NULL)
    {
        if($desde && !$hasta)
          $hasta = $desde;
        else
          if(!$desde && $hasta)
            $desde = $hasta;
          else
            if(!$desde && !$hasta)
            {
                $desde = '1900-01-01';
                $hasta = '2100-01-01';
            }

        $company = Company::where('identification_number', $nit)->first();

        if(!$company)
        {
            return [
                'success' => false,
                'message' => 'No se encontraron datos del NIT',
            ];
        }

        $i = Document::where('state_document_id', 1)->where('identification_number', $company->identification_number)->where('type_document_id', 1)->whereDate('date_issue', '>=', $desde)->whereDate('date_issue', '<=', $hasta)->get();
        $c = Document::where('state_document_id', 1)->where('identification_number', $company->identification_number)->where('type_document_id', 4)->whereDate('date_issue', '>=', $desde)->whereDate('date_issue', '<=', $hasta)->get();
        $d = Document::where('state_document_id', 1)->where('identification_number', $company->identification_number)->where('type_document_id', 5)->whereDate('date_issue', '>=', $desde)->whereDate('date_issue', '<=', $hasta)->get();

        $invoice = (object)[
            'name' => 'Factura de Venta Nacional',
            'count' => count($i),
            'documents' => $i
        ];

        $credit_note = (object)[
            'name' => 'Nota Credito',
            'count' => count($c),
            'documents' => $c
        ];

        $debit_note = (object)[
            'name' => 'Nota Debito',
            'count' => count($d),
            'documents' => $d
        ];

        return [
            'success' => true,
            'message' => 'NIT Encontrado',
            'data'=> array($invoice, $credit_note, $debit_note),
            'company' => $company->user->name
        ];
    }

    public function information_by_page($nit, $page)
    {
        if($page <= 0)
            $page = 1;

        $company = Company::where('identification_number', $nit)->first();

        if(!$company)
        {
            return [
                'success' => false,
                'message' => 'No se encontraron datos del NIT',
            ];
        }

        $perPage = 100;

        $i = Document::where('state_document_id', 1)->where('identification_number', $company->identification_number)->where('type_document_id', 1)->skip(($page - 1) * $perPage)->take($perPage)->oldest()->get();
        $c = Document::where('state_document_id', 1)->where('identification_number', $company->identification_number)->where('type_document_id', 4)->skip(($page - 1) * $perPage)->take($perPage)->oldest()->get();
        $d = Document::where('state_document_id', 1)->where('identification_number', $company->identification_number)->where('type_document_id', 5)->skip(($page - 1) * $perPage)->take($perPage)->oldest()->get();

        $invoice = (object)[
            'name' => 'Factura de Venta Nacional',
            'count' => count($i),
            'documents' => $i
        ];

        $credit_note = (object)[
            'name' => 'Nota Credito',
            'count' => count($c),
            'documents' => $c
        ];

        $debit_note = (object)[
            'name' => 'Nota Debito',
            'count' => count($d),
            'documents' => $d
        ];

        return [
            'success' => true,
            'message' => 'NIT Encontrado',
            'data'=> array($invoice, $credit_note, $debit_note),
            'company' => $company->user->name
        ];
    }

}
