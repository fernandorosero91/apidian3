<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Company;

class DocumentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key) {
            // Obtener el nombre de la empresa por identification_number
            $companyName = 'Sin Empresa';
            if ($row->identification_number) {
                $company = Company::where('identification_number', $row->identification_number)->first();
                if ($company && $company->user) {
                    $companyName = $company->user->name;
                }
            }
            
            // Determinar estado del documento
            $stateId = $row->state_document_id ?? 0;
            $stateName = 'Pendiente';
            $stateClass = 'warning';
            
            if ($row->cufe && strlen($row->cufe) > 10) {
                $stateName = 'Procesado';
                $stateClass = 'success';
            } elseif ($stateId == 1) {
                $stateName = 'Procesado';
                $stateClass = 'success';
            }
            
            // Tipo de documento
            $typeDocId = $row->type_document_id;
            $typeDocName = 'Documento';
            $typeDocIcon = 'file';
            
            switch ($typeDocId) {
                case 1:
                    $typeDocName = 'Factura';
                    $typeDocIcon = 'file-invoice';
                    break;
                case 2:
                    $typeDocName = 'Factura Exportación';
                    $typeDocIcon = 'file-export';
                    break;
                case 3:
                    $typeDocName = 'Factura Contingencia';
                    $typeDocIcon = 'file-alt';
                    break;
                case 4:
                    $typeDocName = 'Nota Crédito';
                    $typeDocIcon = 'file-minus';
                    break;
                case 5:
                    $typeDocName = 'Nota Débito';
                    $typeDocIcon = 'file-plus';
                    break;
                case 6:
                    $typeDocName = 'Nómina';
                    $typeDocIcon = 'users';
                    break;
                case 7:
                    $typeDocName = 'Nómina Ajuste';
                    $typeDocIcon = 'user-edit';
                    break;
                case 11:
                    $typeDocName = 'Doc. Soporte';
                    $typeDocIcon = 'file-contract';
                    break;
                case 12:
                    $typeDocName = 'Nota Ajuste DS';
                    $typeDocIcon = 'file-signature';
                    break;
                case 13:
                    $typeDocName = 'NC Doc. Soporte';
                    $typeDocIcon = 'file-minus';
                    break;
            }
            
            return [
                'key' => $key + 1,
                'id' => $row->id,
                'number' => $row->number,
                'prefix' => $row->prefix,
                'client' => $row->client->name ?? 'N/A',
                'currency' => $row->currency->name ?? 'N/A',
                'date' => $row->date_issue,
                'sale' => $row->sale,
                'total_discount' => $row->total_discount,
                'total_tax' => $row->total_tax,
                'subtotal' => $row->subtotal,
                'total' => $row->total,
                'xml' => $row->xml,
                'pdf' => $row->pdf,
                'company_name' => $companyName,
                'identification_number' => $row->identification_number,
                'state_id' => $stateId,
                'state_name' => $stateName,
                'state_class' => $stateClass,
                'cufe' => $row->cufe,
                'type_document_id' => $typeDocId,
                'type_document_name' => $typeDocName,
                'type_document_icon' => $typeDocIcon,
            ];
        });
    }
    
}
