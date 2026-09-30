<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Company;
use App\User;
use App\Resolution;
use App\Software;
use App\Certificate;
use App\TypeEnvironment;
use App\TypeDocumentIdentification;
use App\TypeOrganization;
use App\TypeRegime;
use App\TypeLiability;
use App\Department;
use App\Municipality;
use App\TypePlan;
use App\Document;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Lista de empresas
     */
    public function index()
    {
        return view('companies.index');
    }

    /**
     * Obtener registros para la tabla
     */
    public function records(Request $request)
    {
        $query = Company::with(['user', 'type_environment', 'resolutions', 'software', 'certificate', 'municipality']);
        
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('identification_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        $companies = $query->orderBy('created_at', 'desc')->get();
        
        $data = $companies->map(function($company) {
            $docsCount = Document::where('identification_number', $company->identification_number)->count();
            
            return [
                'id' => $company->id,
                'user_id' => $company->user_id,
                'identification_number' => $company->identification_number,
                'dv' => $company->dv,
                'name' => $company->user->name ?? 'Sin nombre',
                'email' => $company->user->email ?? '',
                'phone' => $company->phone,
                'address' => $company->address,
                'merchant_registration' => $company->merchant_registration,
                'municipality_id' => $company->municipality_id,
                'municipality_name' => $company->municipality->name ?? '',
                'type_environment_id' => $company->type_environment_id,
                'type_environment_name' => $company->type_environment->name ?? 'Habilitación',
                'type_document_identification_id' => $company->type_document_identification_id,
                'type_organization_id' => $company->type_organization_id,
                'type_regime_id' => $company->type_regime_id,
                'type_liability_id' => $company->type_liability_id,
                'state' => $company->state ?? true,
                'documents_count' => $docsCount,
                'resolutions_count' => $company->resolutions->count(),
                'has_software' => $company->software ? true : false,
                'has_certificate' => $company->certificate ? true : false,
                'created_at' => $company->created_at->format('Y-m-d H:i'),
            ];
        });
        
        return response()->json(['data' => $data]);
    }

    /**
     * Ver detalle de empresa
     */
    public function show($id)
    {
        $company = Company::with(['user', 'software', 'certificate', 'resolutions', 'type_environment', 
            'type_document_identification', 'type_organization', 'type_regime', 'type_liability', 'municipality'])
            ->findOrFail($id);
        
        return view('companies.show', compact('company'));
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $company = Company::with(['user', 'software', 'certificate', 'resolutions'])->findOrFail($id);
        $tables = $this->getTables();
        
        return view('companies.edit', compact('company', 'tables'));
    }

    /**
     * Actualizar empresa
     */
    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            $user = User::findOrFail($company->user_id);
            
            // Actualizar usuario
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
            
            // Actualizar empresa
            $company->update([
                'identification_number' => $request->identification_number,
                'dv' => $request->dv,
                'phone' => $request->phone,
                'address' => $request->address,
                'merchant_registration' => $request->merchant_registration,
                'municipality_id' => $request->municipality_id,
                'type_document_identification_id' => $request->type_document_identification_id,
                'type_organization_id' => $request->type_organization_id,
                'type_regime_id' => $request->type_regime_id,
                'type_liability_id' => $request->type_liability_id,
                'state' => $request->state ?? true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar ambiente (Producción/Habilitación)
     */
    public function changeEnvironment(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->type_environment_id = $request->type_environment_id;
            $company->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Ambiente cambiado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Habilitar/Deshabilitar empresa
     */
    public function toggleState(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->state = !$company->state;
            $company->save();
            
            return response()->json([
                'success' => true,
                'message' => $company->state ? 'Empresa habilitada' : 'Empresa deshabilitada',
                'state' => $company->state
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar empresa
     */
    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            $userId = $company->user_id;
            $nit = $company->identification_number;
            
            // Eliminar resoluciones
            Resolution::where('company_id', $id)->delete();
            
            // Eliminar software
            Software::where('company_id', $id)->delete();
            
            // Eliminar certificado
            Certificate::where('company_id', $id)->delete();
            
            // Eliminar empresa
            $company->delete();
            
            // Eliminar usuario
            User::where('id', $userId)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Empresa {$nit} eliminada correctamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver resoluciones de una empresa
     */
    public function resolutions($id)
    {
        $company = Company::with('resolutions')->findOrFail($id);
        return view('companies.resolutions', compact('company'));
    }

    /**
     * Obtener resoluciones (API)
     */
    public function getResolutions($id)
    {
        $resolutions = Resolution::where('company_id', $id)->get();
        return response()->json(['data' => $resolutions]);
    }

    /**
     * Actualizar resolución
     */
    public function updateResolution(Request $request, $id)
    {
        try {
            $resolution = Resolution::findOrFail($id);
            $resolution->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Resolución actualizada'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar resolución
     */
    public function deleteResolution($id)
    {
        try {
            Resolution::findOrFail($id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Resolución eliminada'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tablas para formularios
     */
    private function getTables()
    {
        return [
            'type_environments' => TypeEnvironment::all(),
            'type_document_identifications' => TypeDocumentIdentification::all(),
            'type_organizations' => TypeOrganization::all(),
            'type_regimes' => TypeRegime::all(),
            'type_liabilities' => TypeLiability::all(),
            'departments' => Department::where('country_id', 46)->get(),
            'municipalities' => Municipality::all(),
            'type_plans' => TypePlan::all(),
        ];
    }

    /**
     * Obtener tablas (API)
     */
    public function tables()
    {
        return response()->json($this->getTables());
    }

    /**
     * Formulario de edición (parcial para modal)
     */
    public function editForm($id)
    {
        $company = Company::with(['user', 'municipality'])->findOrFail($id);
        $tables = $this->getTables();
        
        return view('companies.partials.edit-form', compact('company', 'tables'));
    }

    /**
     * Lista de resoluciones (parcial para modal)
     */
    public function resolutionsList($id)
    {
        $company = Company::with('resolutions')->findOrFail($id);
        
        return view('companies.partials.resolutions-list', compact('company'));
    }

    /**
     * Obtener datos completos de empresa para edición
     */
    public function getCompanyData($id)
    {
        $company = Company::with(['user', 'software', 'certificate', 'resolutions', 'municipality'])
            ->findOrFail($id);
        
        return response()->json([
            'company' => [
                'id' => $company->id,
                'identification_number' => $company->identification_number,
                'dv' => $company->dv,
                'name' => $company->user->name ?? '',
                'email' => $company->user->email ?? '',
                'phone' => $company->phone,
                'address' => $company->address,
                'merchant_registration' => $company->merchant_registration,
                'municipality_id' => $company->municipality_id,
                'department_id' => $company->municipality->department_id ?? null,
                'type_document_identification_id' => $company->type_document_identification_id,
                'type_organization_id' => $company->type_organization_id,
                'type_regime_id' => $company->type_regime_id,
                'type_liability_id' => $company->type_liability_id,
                'type_environment_id' => $company->type_environment_id,
                'state' => $company->state,
            ],
            'software' => $company->software ? [
                'id' => $company->software->id,
                'identifier' => $company->software->identifier,
                'pin' => $company->software->pin,
                'url' => $company->software->url,
            ] : null,
            'certificate' => $company->certificate ? [
                'id' => $company->certificate->id,
                'name' => $company->certificate->name,
                'expiration' => $company->certificate->expiration ?? null,
            ] : null,
            'resolutions' => $company->resolutions->map(function($r) {
                return [
                    'id' => $r->id,
                    'type_document_id' => $r->type_document_id,
                    'prefix' => $r->prefix,
                    'resolution' => $r->resolution,
                    'resolution_date' => $r->resolution_date,
                    'technical_key' => $r->technical_key,
                    'from' => $r->from,
                    'to' => $r->to,
                    'next_consecutive' => $r->next_consecutive,
                    'date_from' => $r->date_from,
                    'date_to' => $r->date_to,
                ];
            }),
        ]);
    }

    /**
     * Actualizar software
     */
    public function updateSoftware(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            
            $software = Software::updateOrCreate(
                ['company_id' => $id],
                [
                    'identifier' => $request->identifier,
                    'pin' => $request->pin,
                    'url' => $request->url,
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Software actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir certificado
     */
    public function uploadCertificate(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            
            if (!$request->hasFile('certificate')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha seleccionado ningún archivo'
                ], 400);
            }
            
            $file = $request->file('certificate');
            $password = $request->password;
            
            // Validar que sea un archivo .p12 o .pfx
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['p12', 'pfx'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo debe ser .p12 o .pfx'
                ], 400);
            }
            
            // Guardar archivo
            $filename = $company->identification_number . '.' . $extension;
            $path = $file->storeAs('certificates', $filename);
            $fullPath = storage_path('app/' . $path);
            
            // Verificar certificado y obtener fecha de expiración
            $expiration = null;
            try {
                $certContent = file_get_contents($fullPath);
                if (openssl_pkcs12_read($certContent, $certs, $password)) {
                    $certInfo = openssl_x509_parse($certs['cert']);
                    if (isset($certInfo['validTo_time_t'])) {
                        $expiration = date('Y-m-d', $certInfo['validTo_time_t']);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contraseña del certificado incorrecta'
                    ], 400);
                }
            } catch (\Exception $e) {
                \Log::warning('Error reading certificate: ' . $e->getMessage());
            }
            
            // Guardar o actualizar en BD
            Certificate::updateOrCreate(
                ['company_id' => $id],
                [
                    'name' => $filename,
                    'password' => $password,
                    'path' => $fullPath,
                    'expiration' => $expiration,
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Certificado cargado correctamente',
                'expiration' => $expiration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear resolución
     */
    public function createResolution(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            
            $resolution = Resolution::create([
                'company_id' => $id,
                'type_document_id' => $request->type_document_id,
                'prefix' => $request->prefix,
                'resolution' => $request->resolution,
                'resolution_date' => $request->resolution_date,
                'technical_key' => $request->technical_key,
                'from' => $request->from,
                'to' => $request->to,
                'next_consecutive' => $request->from,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Resolución creada correctamente',
                'resolution' => $resolution
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener documentos de una empresa
     */
    public function getDocuments($id)
    {
        try {
            $company = Company::findOrFail($id);
            
            $documents = Document::where('identification_number', $company->identification_number)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()
                ->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'type_document_id' => $doc->type_document_id,
                        'prefix' => $doc->prefix,
                        'number' => $doc->number,
                        'total' => $doc->total ?? 0,
                        'state_document_id' => $doc->state_document_id,
                        'cufe' => $doc->cufe,
                        'created_at' => $doc->created_at ? $doc->created_at->format('Y-m-d H:i') : '',
                    ];
                });
            
            return response()->json(['data' => $documents]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
