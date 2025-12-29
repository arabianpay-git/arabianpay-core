<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Merchant;
use App\Models\SupplierBank;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComplianceController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function updateDocument(Request $request, $id)
    {
        $user = currentUser();

        $merchant = Merchant::where('id', $id)
            ->with('user')
            ->when(
                !(
                    ($user->user_type === 'employee' && $user->is_manager) || $user->user_type === 'admin'
                ),
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => __('Supplier not found or not assigned to you.')
            ], 403);
        }

        $request->validate([
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        if ($request->document_type === 'iban_certificate') {
            $validationRules['bank_name'] = 'required|string|max:255';
            $validationRules['account_name'] = 'required|string|max:255';
            $validationRules['iban'] = 'required|string|max:34';
            $validationRules['bank_id'] = 'required';
            $validationRules['file'] = 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120';
        }

        try {
            $filePath = null;
            $fileName = null;
            $fullUrl = null;
            $entityType = null;
            $entityId = null;
            $oldData = null;
            $model = null;

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $extension = strtolower($file->getClientOriginalExtension());
                $fileName = Str::random(40) . '.' . $extension;

                // Store based on document type
                if ($request->document_type === 'contract') {
                    // Store contract in storage/contracts
                    $filePath = $file->storeAs('contracts', $fileName, 'public');
                    // For contract, we store the full URL in database
                    $fullUrl = url('storage/contracts/' . $fileName);
                } else {
                    // Store other documents in storage/media
                    $filePath = $file->storeAs('media', $fileName, 'public');
                    // For other docs, we just store filename in database
                    $fullUrl = url('storage/media/' . $fileName);
                }

                if (!$filePath) {
                    throw new \Exception('File upload failed');
                }
            }

            // Update based on document type
            switch ($request->document_type) {
                case 'cr_file':
                    $oldData = $merchant->toArray();
                    $merchant->update(['registration_number_form' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'vat_file':
                    $oldData = $merchant->toArray();
                    $merchant->update(['vat_register_file' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'return_policy_file':
                    $oldData = $merchant->toArray();
                    $merchant->update(['return_policy_file' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'exchange_policy_file':
                    $oldData = $merchant->toArray();
                    $merchant->update(['exchange_policy_file' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'cancel_policy_file':
                    $oldData = $merchant->toArray();
                    $merchant->update(['cancel_policy_file' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'id_image':
                    $oldData = $merchant->toArray();
                    $merchant->update(['owner_iqama_image' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'balady_certificate':
                    $oldData = $merchant->toArray();
                    $merchant->update(['balady_certificate' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'manager_approval':
                    $oldData = $merchant->toArray();
                    $merchant->update(['manager_approval' => $fileName]); // Store just filename
                    $entityType = 'Merchant';
                    $entityId = $merchant->id;
                    $model = $merchant;
                    break;

                case 'contract':
                    $request->validate([
                        'commission' => 'required|numeric|min:0|max:100',
                        'contract_end_date' => 'required|date',
                    ]);

                    $approval = Approval::where('user_id', $merchant->user_id)->first();
                    $isNew = false;

                    if (!$approval) {
                        $approval = new Approval();
                        $approval->user_id = $merchant->user_id;
                        $approval->employee_id = $user->id;
                        $isNew = true;
                        $oldData = null;
                    } else {
                        $oldData = $approval->toArray();
                    }

                    $approval->commission = $request->commission;
                    $approval->contract_end_date = $request->contract_end_date;
                    // For contract, store the full URL path
                    $approval->contract = 'storage/contracts/' . $fileName;
                    $approval->save();

                    $entityType = 'Approval';
                    $entityId = $approval->id;
                    $model = $approval;

                    // Log creation or update
                    if ($isNew) {
                        $this->auditTrailService->logCreated(
                            $model,
                            "Created new contract for supplier '{$merchant->user->business_name}'",
                            [
                                'supplier_id' => $merchant->id,
                                'business_name' => $merchant->user->business_name,
                                'commission' => $request->commission,
                                'contract_end_date' => $request->contract_end_date,
                                'file_name' => $fileName,
                                'document_type' => 'contract',
                                'justification' => 'Supplier contract agreement',
                                'pdpl_category' => 'legitimate_interest',
                                'pii_fields_involved' => []
                            ]
                        );
                    } else {
                        $this->auditTrailService->logUpdated(
                            $model,
                            $oldData,
                            "Updated contract for supplier '{$merchant->user->business_name}'",
                            [
                                'supplier_id' => $merchant->id,
                                'business_name' => $merchant->user->business_name,
                                'commission' => $request->commission,
                                'contract_end_date' => $request->contract_end_date,
                                'file_name' => $fileName,
                                'document_type' => 'contract',
                                'justification' => 'Supplier contract renewal/update',
                                'pdpl_category' => 'legitimate_interest',
                                'pii_fields_involved' => []
                            ]
                        );
                    }
                    break;

                case 'iban_certificate':
                    $bank = SupplierBank::find($request->bank_id);
                    if ($bank) {
                        // Check if bank belongs to this merchant or related users
                        $mainUserId = $merchant->user->main_user_id ?: $merchant->user_id;
                        $relatedUserIds = \App\Models\User::where('id', $mainUserId)
                            ->orWhere('main_user_id', $mainUserId)
                            ->pluck('id');

                        if ($relatedUserIds->contains($bank->user_id)) {
                            $oldData = $bank->toArray();

                            // Update bank details
                            $bank->bank_name = $request->bank_name;
                            $bank->account_name = $request->account_name;
                            $bank->iban = $request->iban;

                            // Update certificate file if provided
                            if ($fileName) {
                                $bank->iban_certificate = $fileName; // Store just filename
                            }

                            $bank->save();

                            $entityType = 'SupplierBank';
                            $entityId = $bank->id;
                            $model = $bank;
                        } else {
                            throw new \Exception('Bank not found or not authorized');
                        }
                    } else {
                        throw new \Exception('Bank not found');
                    }
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid document type.')
                    ], 400);
            }

            // Log action for non-contract document types
            if ($request->document_type !== 'contract') {
                $actionSummary = match ($request->document_type) {
                    'cr_file' => "Updated CR file for supplier '{$merchant->user->business_name}'",
                    'vat_file' => "Updated VAT register file for supplier '{$merchant->user->business_name}'",
                    'return_policy_file' => "Updated return policy file for supplier '{$merchant->user->business_name}'",
                    'exchange_policy_file' => "Updated delivery policy file for supplier '{$merchant->user->business_name}'",
                    'cancel_policy_file' => "Updated cancel policy file for supplier '{$merchant->user->business_name}'",
                    'id_image' => "Updated ID image for supplier '{$merchant->user->business_name}'",
                    'balady_certificate' => "Updated balady certificate for supplier '{$merchant->user->business_name}'",
                    'manager_approval' => "Updated manager approval letter for supplier '{$merchant->user->business_name}'",
                    'iban_certificate' => "Updated IBAN certificate for bank #{$bank->id} of supplier '{$merchant->user->business_name}'",
                    default => "Updated document for supplier '{$merchant->user->business_name}'"
                };

                if ($request->document_type === 'iban_certificate') {
                    // Use logUpdated for SupplierBank
                    $this->auditTrailService->logUpdated(
                        $model,
                        $oldData,
                        $actionSummary,
                        [
                            'supplier_id' => $merchant->id,
                            'business_name' => $merchant->user->business_name,
                            'file_name' => $fileName,
                            'document_type' => $request->document_type,
                            'bank_id' => $bank->id,
                            'justification' => 'Bank verification document update',
                            'pdpl_category' => 'legitimate_interest',
                            'pii_fields_involved' => ['iban', 'account_name']
                        ]
                    );
                } else {
                    // Use logUpdated for Merchant
                    $this->auditTrailService->logUpdated(
                        $model,
                        $oldData,
                        $actionSummary,
                        array_merge(
                            [
                                'supplier_id' => $merchant->id,
                                'business_name' => $merchant->user->business_name,
                                'file_name' => $fileName,
                                'document_type' => $request->document_type,
                            ],
                            // Add PII fields for sensitive documents
                            match ($request->document_type) {
                                'id_image' => [
                                    'justification' => 'Identity verification',
                                    'pdpl_category' => 'legal_obligation',
                                    'pii_fields_involved' => ['owner_iqama_number', 'owner_name']
                                ],
                                'cr_file' => [
                                    'justification' => 'Business registration verification',
                                    'pdpl_category' => 'legal_obligation',
                                    'pii_fields_involved' => ['cr_number']
                                ],
                                default => [
                                    'justification' => 'Compliance document update',
                                    'pdpl_category' => 'legitimate_interest',
                                    'pii_fields_involved' => []
                                ]
                            }
                        )
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Document updated successfully!'),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'document_type' => $request->document_type,
                'full_url' => $fullUrl
            ]);
        } catch (\Exception $e) {
            // Log error in audit trail
            $this->auditTrailService->log([
                'event_category' => 'error',
                'event_type' => 'document_update_failed',
                'entity_type' => 'Supplier',
                'entity_id' => $merchant->id ?? null,
                'action_summary' => "Failed to update document for supplier",
                'properties' => [
                    'document_type' => $request->document_type ?? 'unknown',
                    'error_message' => $e->getMessage(),
                    'supplier_id' => $id,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getContractData($id)
    {
        $user = currentUser();

        $merchant = Merchant::where('id', $id)
            ->when(
                !(
                    ($user->user_type === 'employee' && $user->is_manager) || $user->user_type === 'admin'
                ),
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => __('Supplier not found or not assigned to you.')
            ], 403);
        }

        // Log view of contract data
        $this->auditTrailService->logViewOperation(
            'view_contract_data',
            'Supplier',
            "Viewed contract data for supplier '{$merchant->user->business_name}'",
            [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
                'user_id' => $merchant->user_id,
            ]
        );

        $approval = Approval::where('user_id', $merchant->user_id)->first();

        // Build contract data with URLs
        $contractData = null;
        if ($approval) {
            // Check if contract already has full URL path or just filename
            $contractUrl = $approval->contract;

            // If it's just a filename, build the full URL
            if ($contractUrl && !str_contains($contractUrl, '/')) {
                $contractUrl = url('storage/contracts/' . $contractUrl);
            } elseif ($contractUrl && !str_starts_with($contractUrl, 'http')) {
                // If it has path but not full URL, make it a full URL
                $contractUrl = url($contractUrl);
            }

            $contractData = [
                'id' => $approval->id,
                'commission' => $approval->commission,
                'contract_end_date' => $approval->contract_end_date,
                'contract' => $approval->contract,
                'formatted_end_date' => $approval->contract_end_date ? \Carbon\Carbon::parse($approval->contract_end_date)->format('Y-m-d') : null,
                'contract_url' => $contractUrl,
            ];
        }

        return response()->json([
            'success' => true,
            'contract' => $contractData,
            'has_contract' => !empty($approval?->contract)
        ]);
    }
}
