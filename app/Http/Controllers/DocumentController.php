<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Document;
use App\DocumentPayroll;
use App\Company;
use App\User;
use App\Http\Resources\DocumentCollection;
use ubl21dian\XAdES\SignInvoice;
use ubl21dian\Templates\SOAP\SendBillSync;
use Carbon\Carbon;



class DocumentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {   
       // $list =  new CompaniesCollection(User::all());
        //return json_encode($list);
        return view('documents.index') ; 
    }

   

    public function records(Request $request)
    {
        // Verificar si es consulta específica de nómina (type 6 o 7)
        $isPayrollOnly = $request->has('type') && in_array($request->type, ['6', '7', '9', '10', 6, 7, 9, 10]);
        
        if ($isPayrollOnly) {
            return $this->recordsPayroll($request);
        }
        
        // Si no hay filtro de tipo, combinar documentos normales + nómina
        $includePayroll = !$request->has('type') || !$request->type;
        
        $query = Document::query();
        
        // Filtrar por empresa (identification_number)
        if ($request->has('company') && $request->company) {
            $query->where('identification_number', $request->company);
        }
        
        // Filtrar por tipo de documento
        if ($request->has('type') && $request->type) {
            $query->where('type_document_id', $request->type);
        }
        
        // Búsqueda unificada (número o cliente)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('number', 'like', '%' . $search . '%')
                  ->orWhere('client->name', 'like', '%' . $search . '%');
            });
        }
        
        // Paginación
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Si incluimos nómina, necesitamos combinar resultados
        if ($includePayroll) {
            return $this->recordsCombined($request, $perPage, $page);
        }
        
        $total = $query->count();
        $records = $query->orderBy('created_at', 'desc')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get();
        
        // Procesar documentos con información adicional
        $data = $this->processDocuments($records);
        
        return response()->json([
            'data' => $data,
            'total' => $total,
            'page' => (int)$page,
            'per_page' => (int)$perPage,
            'last_page' => ceil($total / $perPage)
        ]);
    }

    /**
     * Combinar documentos normales y de nómina
     */
    private function recordsCombined(Request $request, int $perPage, int $page)
    {
        // Query para documentos normales
        $docsQuery = Document::query();
        if ($request->has('company') && $request->company) {
            $docsQuery->where('identification_number', $request->company);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $docsQuery->where(function($q) use ($search) {
                $q->where('number', 'like', '%' . $search . '%')
                  ->orWhere('client->name', 'like', '%' . $search . '%');
            });
        }
        
        // Query para nómina
        $payrollQuery = DocumentPayroll::query();
        if ($request->has('company') && $request->company) {
            $payrollQuery->where('identification_number', $request->company);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $payrollQuery->where(function($q) use ($search) {
                $q->where('consecutive', 'like', '%' . $search . '%')
                  ->orWhere('prefix', 'like', '%' . $search . '%');
            });
        }
        
        // Contar totales
        $totalDocs = $docsQuery->count();
        $totalPayroll = $payrollQuery->count();
        $total = $totalDocs + $totalPayroll;
        
        // Calcular offset
        $offset = ($page - 1) * $perPage;
        
        // Obtener solo los registros necesarios usando UNION con paginación
        // Primero obtenemos más registros de los necesarios para poder ordenar correctamente
        $limit = $perPage + $offset;
        
        $docs = $docsQuery->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
        
        $payrolls = $payrollQuery->orderBy('created_at', 'desc')
                                 ->limit($limit)
                                 ->get();
        
        // Procesar y combinar
        $allData = [];
        
        foreach ($docs as $row) {
            $allData[] = $this->processDocument($row);
        }
        
        foreach ($payrolls as $row) {
            $allData[] = $this->processPayrollDocument($row);
        }
        
        // Ordenar por fecha descendente
        usort($allData, function($a, $b) {
            return strtotime($b['date'] ?? '1970-01-01') - strtotime($a['date'] ?? '1970-01-01');
        });
        
        // Paginar manualmente
        $data = array_slice($allData, $offset, $perPage);
        
        // Reindexar keys
        foreach ($data as $index => &$item) {
            $item['key'] = $offset + $index + 1;
        }
        
        return response()->json([
            'data' => array_values($data),
            'total' => $total,
            'page' => (int)$page,
            'per_page' => (int)$perPage,
            'last_page' => ceil($total / $perPage)
        ]);
    }

    /**
     * Procesar un documento normal
     */
    private function processDocument($row, $company = null): array
    {
        $environment = 2;
        if ($company) {
            $environment = $company->type_environment_id ?? 2;
        } elseif ($row->identification_number) {
            // Cache de companies para evitar múltiples queries
            static $companiesCache = [];
            $nit = $row->identification_number;
            if (!isset($companiesCache[$nit])) {
                $companiesCache[$nit] = Company::where('identification_number', $nit)->first();
            }
            if ($companiesCache[$nit] && $companiesCache[$nit]->type_environment_id) {
                $environment = $companiesCache[$nit]->type_environment_id;
            }
        }
        
        $stateId = $row->state_document_id ?? 0;
        $hasCufe = $row->cufe && strlen($row->cufe) > 10;
        
        $stateName = 'Pendiente';
        $stateClass = 'warning';
        $canResend = true;
        
        if ($stateId == 1 && $hasCufe) {
            $stateName = 'Procesado';
            $stateClass = 'success';
            $canResend = false;
        } elseif ($stateId == 1 && !$hasCufe) {
            $stateName = 'Enviado';
            $stateClass = 'info';
        }
        
        $typeDocId = $row->type_document_id;
        $typeDocName = 'Documento';
        switch ($typeDocId) {
            case 1: $typeDocName = 'Factura'; break;
            case 2: $typeDocName = 'Factura Exp.'; break;
            case 3: $typeDocName = 'Contingencia'; break;
            case 4: $typeDocName = 'Nota Crédito'; break;
            case 5: $typeDocName = 'Nota Débito'; break;
            case 11: $typeDocName = 'Doc. Soporte'; break;
            case 12: $typeDocName = 'Nota Ajuste DS'; break;
            case 13: $typeDocName = 'NC Doc. Soporte'; break;
        }
        
        return [
            'key' => 0,
            'id' => $row->id,
            'number' => $row->number,
            'prefix' => $row->prefix,
            'client' => $row->client->name ?? 'N/A',
            'date' => $row->date_issue,
            'total' => $row->total,
            'xml' => $row->xml,
            'pdf' => $row->pdf,
            'cufe' => $row->cufe,
            'state_id' => $stateId,
            'state_name' => $stateName,
            'state_class' => $stateClass,
            'can_resend' => $canResend,
            'type_document_name' => $typeDocName,
            'type_document_id' => $typeDocId,
            'environment' => $environment,
            'identification_number' => $row->identification_number,
            'is_payroll' => false,
        ];
    }

    /**
     * Procesar un documento de nómina
     */
    private function processPayrollDocument($row, $company = null): array
    {
        $environment = 2;
        if ($company) {
            $environment = $company->payroll_type_environment_id ?? 2;
        } elseif ($row->identification_number) {
            // Cache de companies para evitar múltiples queries
            static $companiesCache = [];
            $nit = $row->identification_number;
            if (!isset($companiesCache[$nit])) {
                $companiesCache[$nit] = Company::where('identification_number', $nit)->first();
            }
            if ($companiesCache[$nit] && $companiesCache[$nit]->payroll_type_environment_id) {
                $environment = $companiesCache[$nit]->payroll_type_environment_id;
            }
        }
        
        $stateId = $row->state_document_id ?? 0;
        $hasCune = !empty($row->cune) && strlen($row->cune) > 10;
        
        $stateName = 'Pendiente';
        $stateClass = 'warning';
        $canResend = true;
        
        // Si state_document_id es 1, está procesado
        if ($stateId == 1) {
            $stateName = 'Procesado';
            $stateClass = 'success';
            $canResend = false;
        }
        
        $typeDocId = $row->type_document_id;
        $typeDocName = $typeDocId == 9 ? 'Nómina' : 'Nómina Ajuste';
        
        return [
            'key' => 0,
            'id' => $row->id,
            'number' => $row->prefix . $row->consecutive,
            'prefix' => $row->prefix,
            'client' => 'Empleado #' . ($row->employee_id ?? 'N/A'),
            'date' => $row->date_issue,
            'total' => $row->total_payroll,
            'xml' => $row->xml,
            'pdf' => $row->pdf,
            'cufe' => $row->cune,
            'state_id' => $stateId,
            'state_name' => $stateName,
            'state_class' => $stateClass,
            'can_resend' => $canResend,
            'type_document_name' => $typeDocName,
            'type_document_id' => $typeDocId,
            'environment' => $environment,
            'identification_number' => $row->identification_number,
            'is_payroll' => true,
        ];
    }

    /**
     * Procesar lista de documentos normales
     */
    private function processDocuments($records): array
    {
        $data = [];
        foreach ($records as $key => $row) {
            $item = $this->processDocument($row);
            $item['key'] = $key + 1;
            $data[] = $item;
        }
        return $data;
    }

    /**
     * Obtener registros de nómina electrónica
     */
    private function recordsPayroll(Request $request)
    {
        $query = DocumentPayroll::query();
        
        // Filtrar por empresa (identification_number)
        if ($request->has('company') && $request->company) {
            $query->where('identification_number', $request->company);
        }
        
        // Filtrar por tipo de documento
        // Frontend envía: 6 = Nómina, 7 = Nómina Ajuste
        // BD usa: 9 = NI (Nómina Individual), 10 = NA (Nota de Ajuste)
        if ($request->has('type') && $request->type) {
            $typeId = $request->type;
            if (in_array($typeId, ['6', 6, '9', 9])) {
                $query->where('type_document_id', 9); // NI - Nómina Individual
            } elseif (in_array($typeId, ['7', 7, '10', 10])) {
                $query->where('type_document_id', 10); // NA - Nota de Ajuste
            }
        }
        
        // Búsqueda por consecutivo o empleado
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('consecutive', 'like', '%' . $search . '%')
                  ->orWhere('prefix', 'like', '%' . $search . '%');
            });
        }
        
        // Paginación
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $total = $query->count();
        $records = $query->orderBy('created_at', 'desc')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get();
        
        // Procesar documentos
        $data = [];
        foreach ($records as $key => $row) {
            $item = $this->processPayrollDocument($row);
            $item['key'] = $key + 1;
            $data[] = $item;
        }
        
        return response()->json([
            'data' => $data,
            'total' => $total,
            'page' => (int)$page,
            'per_page' => (int)$perPage,
            'last_page' => ceil($total / $perPage)
        ]);
    }


    public function downloadxml($xml)
    {
        // Buscar el documento por nombre de XML
        $document = Document::where('xml', $xml)->first();
        
        if (!$document) {
            return response()->json(['error' => 'Documento no encontrado en BD', 'xml' => $xml], 404);
        }
        
        $nit = $document->identification_number;
        
        // El archivo en BD es FES-xxx.xml pero el archivo físico es FE-xxx.xml (sin la S)
        // Crear variantes del nombre
        $xmlVariants = [$xml];
        
        // Si empieza con FES-, también buscar FE-
        if (strpos($xml, 'FES-') === 0) {
            $xmlVariants[] = str_replace('FES-', 'FE-', $xml);
        }
        // Si empieza con NCS-, también buscar NC-
        if (strpos($xml, 'NCS-') === 0) {
            $xmlVariants[] = str_replace('NCS-', 'NC-', $xml);
        }
        // Si empieza con NDS-, también buscar ND-
        if (strpos($xml, 'NDS-') === 0) {
            $xmlVariants[] = str_replace('NDS-', 'ND-', $xml);
        }
        
        // Intentar varias rutas posibles con todas las variantes
        foreach ($xmlVariants as $xmlName) {
            $possiblePaths = [
                storage_path("app/public/{$nit}/{$xmlName}"),
                storage_path("app/{$nit}/{$xmlName}"),
                storage_path("app/public/{$xmlName}"),
            ];
            
            foreach ($possiblePaths as $filePath) {
                if (file_exists($filePath)) {
                    return response()->download($filePath, $xml); // Descargar con nombre original
                }
            }
        }
        
        return response()->json([
            'error' => 'Archivo XML no encontrado',
            'xml' => $xml,
            'nit' => $nit,
            'variants_tried' => $xmlVariants
        ], 404);
    }

    public function downloadpdf($pdf)
    {
        // Buscar el documento por nombre de PDF
        $document = Document::where('pdf', $pdf)->first();
        
        if (!$document) {
            return response()->json(['error' => 'Documento no encontrado en BD', 'pdf' => $pdf], 404);
        }
        
        $nit = $document->identification_number;
        
        // El archivo en BD es FES-xxx.pdf pero el archivo físico es FE-xxx.pdf (sin la S)
        $pdfVariants = [$pdf];
        
        if (strpos($pdf, 'FES-') === 0) {
            $pdfVariants[] = str_replace('FES-', 'FE-', $pdf);
        }
        if (strpos($pdf, 'NCS-') === 0) {
            $pdfVariants[] = str_replace('NCS-', 'NC-', $pdf);
        }
        if (strpos($pdf, 'NDS-') === 0) {
            $pdfVariants[] = str_replace('NDS-', 'ND-', $pdf);
        }
        
        foreach ($pdfVariants as $pdfName) {
            $possiblePaths = [
                storage_path("app/public/{$nit}/{$pdfName}"),
                storage_path("app/{$nit}/{$pdfName}"),
                storage_path("app/public/{$pdfName}"),
            ];
            
            foreach ($possiblePaths as $filePath) {
                if (file_exists($filePath)) {
                    return response()->download($filePath, $pdf);
                }
            }
        }
        
        return response()->json([
            'error' => 'Archivo PDF no encontrado',
            'pdf' => $pdf,
            'nit' => $nit,
            'variants_tried' => $pdfVariants
        ], 404);
    }

    /**
     * Reenviar documento a la DIAN
     */
    public function resend($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Verificar que el documento no esté ya procesado
            if ($document->state_document_id == 1 && $document->cufe && strlen($document->cufe) > 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este documento ya fue procesado exitosamente por la DIAN.'
                ], 400);
            }
            
            // Obtener la empresa (con sus relaciones: certificate, software)
            $company = Company::where('identification_number', $document->identification_number)->first();
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la empresa asociada al documento.'
                ], 404);
            }
            
            // Obtener el usuario de la empresa (Company belongsTo User via user_id)
            $user = $company->user;
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el usuario de la empresa.'
                ], 404);
            }
            
            $nit = $document->identification_number;
            $xml = $document->xml;
            
            // Determinar el prefijo del archivo según el tipo de documento
            $xmlPrefix = 'FES';
            $typeDocId = $document->type_document_id;
            switch ($typeDocId) {
                case 4: $xmlPrefix = 'NCS'; break;
                case 5: $xmlPrefix = 'NDS'; break;
                case 11: $xmlPrefix = 'DSS'; break;
                case 12: $xmlPrefix = 'NADS'; break;
            }
            
            // Buscar el archivo XML firmado
            $xmlPath = null;
            $possiblePaths = [
                storage_path("app/public/{$nit}/{$xml}"),
                storage_path("app/{$nit}/{$xml}"),
                storage_path("app/public/{$xml}"),
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $xmlPath = $path;
                    break;
                }
            }
            
            if (!$xmlPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el archivo XML firmado del documento.',
                    'paths_tried' => $possiblePaths
                ], 404);
            }
            
            // Buscar el archivo ZIP
            $zipName = str_replace('.xml', '.zip', $xml);
            $zipPath = null;
            $possibleZipPaths = [
                storage_path("app/public/{$nit}/{$zipName}"),
                storage_path("app/{$nit}/{$zipName}"),
                storage_path("app/public/{$zipName}"),
            ];
            
            foreach ($possibleZipPaths as $path) {
                if (file_exists($path)) {
                    $zipPath = $path;
                    break;
                }
            }
            
            if (!$zipPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el archivo ZIP del documento.',
                    'paths_tried' => $possibleZipPaths
                ], 404);
            }
            
            // Leer el contenido del ZIP en base64
            $zipContent = base64_encode(file_get_contents($zipPath));
            
            // Preparar el envío a la DIAN
            $sendBillSync = new SendBillSync($company->certificate->path, $company->certificate->password);
            $sendBillSync->To = $company->software->url;
            $sendBillSync->fileName = str_replace($xmlPrefix . '-', '', $xml);
            $sendBillSync->contentFile = $zipContent;
            
            // Enviar a la DIAN
            $reqPath = storage_path("app/public/{$nit}/Req-Resend-{$document->id}.xml");
            $rptaPath = storage_path("app/public/{$nit}/Rpta-Resend-{$document->id}.xml");
            
            $respuestadian = $sendBillSync->signToSend($reqPath)->getResponseToObject($rptaPath);
            
            \Log::info('Resend DIAN Response for document ' . $document->id, ['response' => json_encode($respuestadian)]);
            
            // Verificar si hay error de servicio
            if (isset($respuestadian->html)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El servicio DIAN no se encuentra disponible en el momento, reintente más tarde.'
                ], 503);
            }
            
            // Verificar respuesta
            $isValid = false;
            $cufe = null;
            $statusMessage = '';
            
            if (isset($respuestadian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult)) {
                $result = $respuestadian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult;
                $isValid = ($result->IsValid == 'true');
                
                if ($isValid) {
                    $cufe = $result->XmlDocumentKey ?? null;
                    $statusMessage = $result->StatusMessage ?? 'Documento procesado exitosamente';
                    
                    // Actualizar documento
                    $document->state_document_id = 1;
                    $document->cufe = $cufe;
                    $document->save();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Documento reenviado y procesado exitosamente por la DIAN.',
                        'cufe' => $cufe,
                        'dian_response' => $statusMessage
                    ]);
                } else {
                    // Extraer mensajes de error
                    $errorMessages = [];
                    if (isset($result->ErrorMessage)) {
                        if (is_array($result->ErrorMessage->string)) {
                            $errorMessages = $result->ErrorMessage->string;
                        } else {
                            $errorMessages[] = $result->ErrorMessage->string ?? $result->ErrorMessage;
                        }
                    }
                    $statusMessage = $result->StatusMessage ?? 'Error en validación DIAN';
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'La DIAN rechazó el documento.',
                        'dian_status' => $statusMessage,
                        'errors' => $errorMessages,
                        'response' => $respuestadian
                    ], 400);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Respuesta inesperada de la DIAN.',
                'response' => $respuestadian
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('Error resending document ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar documento: ' . $e->getMessage()
            ], 500);
        }
    }
}
