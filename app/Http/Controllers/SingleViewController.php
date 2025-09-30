<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\SupplierBank;
use App\Models\UserConsent;
use App\Services\SingleViewService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class SingleViewController extends Controller
{
    protected SingleViewService $singleViewService;

    public function __construct(SingleViewService $singleViewService)
    {
        $this->singleViewService = $singleViewService;
    }

    /**
     * Check if current user has access to merchant
     */
    private function checkUser($id): Merchant
    {
        $user = currentUser();

        $merchant = Merchant::where('user_id', $id)
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
            abort(404, __('Supplier not found or not assigned to you.'));
        }

        return $merchant;
    }

    /**
     * Show supplier SingleView page
     */
    public function index($id): \Illuminate\View\View
    {
        $merchant = $this->checkUser($id);

        $supplierBank = SupplierBank::where('user_id', $merchant->user_id)->first();

        return view('admin.accounts.supplier-singleview', [
            'merchant'     => $merchant,
            'supplierBank' => $supplierBank,
        ]);
    }

    /**
     * AJAX: fetch accounts (called by the Get Data button)
     */
    public function fetchAccountsAjax($id): JsonResponse
    {
        $merchant = $this->checkUser($id);
        $supplierBank = SupplierBank::where('user_id', $merchant->user_id)->first();

        if (!$supplierBank) {
            return response()->json(['success' => false, 'payload' => []]);
        }

        $accounts = $this->getAccounts($supplierBank, $id);

        if (is_array($accounts) && isset($accounts['redirect_url'])) {
            return response()->json(['success' => false, 'redirect_url' => $accounts['redirect_url']]);
        }

        return response()->json($accounts);
    }

    /**
     * Internal: fetch accounts for a given SupplierBank
     */
    private function getAccounts(?SupplierBank $bank, $userId): array
    {
        if (!$bank) return [];

        $bankCode = $bank->code ?? 'SVMOB1';
        $iban = $bank->iban ?? '';
        $ibanCheck = true;

        $userConsent = UserConsent::where('user_id', $userId)
            ->where('bank_code', $bankCode)
            ->first();
        $consentId = $userConsent->consent_id ?? null;

        if (empty($consentId)) {
            Log::info('No consent found for user, creating consent', [
                'user_id' => $userId,
                'bank_code' => $bankCode,
            ]);

            $url = $this->createConsentAndGetUrl($userId, $bankCode);
            return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Failed to create consent.']];
        }

        try {
            $result = $this->singleViewService->getAccounts($bankCode, $consentId, $iban, $ibanCheck);

            if ($this->isConsentInvalid($result, $bankCode)) {
                Log::info('Consent invalid — creating new consent', [
                    'user_id' => $userId,
                    'bank_code' => $bankCode,
                    'api_response' => $result,
                ]);

                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return $result;
        } catch (Exception $e) {
            Log::error('getAccounts exception caught', [
                'bank_id' => $bank->id,
                'user_id' => $userId,
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (stripos($e->getMessage(), 'Invalid Consent Data') !== false) {
                Log::info('Caught Invalid Consent Data exception, attempting to create new consent.');
                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return ['success' => false, 'payload' => ['message' => 'An error occurred while fetching accounts.']];
        }
    }

    /**
     * AJAX: fetch accounts balance (Get Balance button)
     */
    public function fetchAccountsBalance($id): JsonResponse
    {
        $merchant = $this->checkUser($id);
        $supplierBank = SupplierBank::where('user_id', $merchant->user_id)->first();

        if (!$supplierBank) {
            return response()->json(['success' => false, 'payload' => []]);
        }

        $accounts = $this->getAccountsBalance($supplierBank, $id);

        if (is_array($accounts) && isset($accounts['redirect_url'])) {
            return response()->json(['success' => false, 'redirect_url' => $accounts['redirect_url']]);
        }

        return response()->json($accounts);
    }

    /**
     * Internal: get accounts balance for a bank (same return semantics as getAccounts)
     */
    private function getAccountsBalance(?SupplierBank $bank, $userId): array
    {
        if (!$bank) return [];

        $bankCode = $bank->code ?? 'SVMOB1';

        $userConsent = UserConsent::where('user_id', $userId)
            ->where('bank_code', $bankCode)
            ->first();
        $consentId = $userConsent->consent_id ?? null;

        if (empty($consentId)) {
            Log::info('No consent found for user, creating consent', [
                'user_id' => $userId,
                'bank_code' => $bankCode,
            ]);

            $url = $this->createConsentAndGetUrl($userId, $bankCode);
            return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Failed to create consent.']];
        }

        try {
            $result = $this->singleViewService->getAllAccountsBalance($bankCode, $consentId);

            if ($this->isConsentInvalid($result, $bankCode)) {
                Log::info('Consent invalid — creating new consent', [
                    'user_id' => $userId,
                    'bank_code' => $bankCode,
                    'api_response' => $result,
                ]);

                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return $result;
        } catch (Exception $e) {
            Log::error('getAccountsBalance exception caught', [
                'bank_id' => $bank->id,
                'user_id' => $userId,
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (stripos($e->getMessage(), 'Invalid Consent Data') !== false) {
                Log::info('Caught Invalid Consent Data exception, attempting to create new consent.');
                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return ['success' => false, 'payload' => ['message' => 'An error occurred while fetching balances.']];
        }
    }

    public function fetchCreditCheck($id): JsonResponse
    {
        $merchant = $this->checkUser($id);
        $supplierBank = SupplierBank::where('user_id', $merchant->user_id)->first();

        if (!$supplierBank) {
            return response()->json(['success' => false, 'payload' => []]);
        }

        $accounts = $this->getCreditCheckBasic($supplierBank, $id);

        if (is_array($accounts) && isset($accounts['redirect_url'])) {
            return response()->json(['success' => false, 'redirect_url' => $accounts['redirect_url']]);
        }

        return response()->json($accounts);
    }

    private function getCreditCheckBasic(?SupplierBank $bank, $userId): array
    {
        if (!$bank) return [];

        $bankCode = $bank->code ?? 'SVMOB1';

        $userConsent = UserConsent::where('user_id', $userId)
            ->where('bank_code', $bankCode)
            ->first();
        $consentId = $userConsent->consent_id ?? null;

        if (empty($consentId)) {
            Log::info('No consent found for user, creating consent', [
                'user_id' => $userId,
                'bank_code' => $bankCode,
            ]);

            $url = $this->createConsentAndGetUrl($userId, $bankCode);
            return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Failed to create consent.']];
        }

        try {
            $fromDate = request()->query('fromDate');
            $toDate   = request()->query('toDate');
            $creditCheck = filter_var(request()->query('creditCheck', true), FILTER_VALIDATE_BOOLEAN);
            $result = $this->singleViewService->creditCheckBasic($bankCode, $consentId, $fromDate, $toDate, $creditCheck);

            if ($this->isConsentInvalid($result, $bankCode)) {
                Log::info('Consent invalid — creating new consent', [
                    'user_id' => $userId,
                    'bank_code' => $bankCode,
                    'api_response' => $result,
                ]);

                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return $result;
        } catch (Exception $e) {
            Log::error('getCreditCheckBasic exception caught', [
                'bank_id' => $bank->id,
                'user_id' => $userId,
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (stripos($e->getMessage(), 'Invalid Consent Data') !== false) {
                Log::info('Caught Invalid Consent Data exception, attempting to create new consent.');
                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return ['success' => false, 'payload' => ['message' => 'An error occurred while fetching balances.']];
        }
    }

    /**
     * Detect whether API result indicates invalid consent
     */
    private function isConsentInvalid(array $result, string $bankCode): bool
    {
        if (!isset($result['success']) || $result['success'] !== false) {
            return false;
        }

        if (empty($result['payload']) || !is_array($result['payload'])) {
            return false;
        }

        foreach ($result['payload'] as $p) {
            if (!empty($p['message']) && stripos($p['message'], 'Invalid Consent Data') !== false) {
                return true;
            }

            if (!empty($p['code']) && !empty($p['consentId']) && stripos($p['code'], $bankCode) !== false) {
                if (!empty($p['message']) && stripos($p['message'], 'Invalid Consent') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Create consent via the SingleViewService, persist consent_id and return the bankRedirectUrl (string)
     * or null if creation failed or no redirect URL present.
     */
    private function createConsentAndGetUrl(int $userId, string $bankCode = 'SVMOB1'): ?string
    {
        try {
            Log::info('Attempting to create new consent', ['user_id' => $userId, 'bank_code' => $bankCode]);
            $redirectUrl = request()->headers->get('referer') ?? url()->current();
            $consentResponse = $this->singleViewService->createConsent($redirectUrl, $bankCode);
            Log::info('Response from createConsent service:', ['response' => $consentResponse]);

            $payloadItem = $consentResponse['payload'][0] ?? null;
            $dataNode = $payloadItem['data'] ?? $payloadItem ?? null;
            $consentId = $dataNode['consentId'] ?? $dataNode['consent_id'] ?? null;
            $bankRedirectUrl = $payloadItem['bankRedirectUrl'] ?? $payloadItem['bank_redirect_url'] ?? null;

            if (!empty($consentId)) {
                Log::info('Consent ID found, attempting to update database.', ['consent_id' => $consentId]);
                UserConsent::updateOrCreate(
                    ['user_id' => $userId, 'bank_code' => $bankCode],
                    ['consent_id' => $consentId]
                );
                Log::info('Database updateOrCreate successful.');
            }

            if (!empty($bankRedirectUrl)) {
                Log::info('Redirect URL found, returning.', ['url' => $bankRedirectUrl]);
                return $bankRedirectUrl;
            }

            Log::warning('Consent created but bankRedirectUrl missing', [
                'user_id' => $userId,
                'bank_code' => $bankCode,
                'consentResponse' => $consentResponse,
            ]);
        } catch (Exception $e) {
            Log::error('SingleView createConsent failed with exception', [
                'user_id' => $userId,
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return null;
    }

    /**
     * Public route to create consent and redirect the user immediately (non-AJAX).
     */
    public function createConsent(Request $request, $userId): RedirectResponse
    {
        $bankCode = $request->query('bankCode', 'SVMOB1');
        $url = $this->createConsentAndGetUrl((int)$userId, $bankCode);

        if ($url) {
            return redirect()->away($url);
        }

        return redirect()->back()->with('error', 'Could not create consent or no redirect URL provided by bank.');
    }











    /**
     * NEW: AJAX entry point called by blade to fetch Income Check Advanced (same semantics as other fetch* methods)
     */
    public function fetchIncomeCheckAdvanced($id): JsonResponse
    {
        $merchant = $this->checkUser($id);
        $supplierBank = SupplierBank::where('user_id', $merchant->user_id)->first();

        if (!$supplierBank) {
            return response()->json(['success' => false, 'payload' => []]);
        }

        $result = $this->getIncomeCheckAdvanced($supplierBank, $id);

        if (is_array($result) && isset($result['redirect_url'])) {
            return response()->json(['success' => false, 'redirect_url' => $result['redirect_url']]);
        }

        return response()->json($result);
    }

    /**
     * NEW: Internal: get Income Check Advanced — mirrors existing pattern for consent handling & error handling.
     */
    private function getIncomeCheckAdvanced(?SupplierBank $bank, $userId): array
    {
        if (!$bank) return [];

        $bankCode = $bank->code ?? 'SVMOB1';

        $userConsent = UserConsent::where('user_id', $userId)
            ->where('bank_code', $bankCode)
            ->first();
        $consentId = $userConsent->consent_id ?? null;

        if (empty($consentId)) {
            Log::info('No consent found for user, creating consent (income check)', [
                'user_id' => $userId,
                'bank_code' => $bankCode,
            ]);

            $url = $this->createConsentAndGetUrl($userId, $bankCode);
            return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Failed to create consent.']];
        }

        try {
            $fromDate = request()->query('fromDate');
            $toDate   = request()->query('toDate');
            $timeLine = request()->query('timeLine', 'byDay');
            $incomeCheck = filter_var(request()->query('incomeCheck', true), FILTER_VALIDATE_BOOLEAN);

            $result = $this->singleViewService->incomeCheckAdvanced(
                $bankCode,
                $consentId,
                $fromDate,
                $toDate,
                $timeLine,
                $incomeCheck
            );

            if ($this->isConsentInvalid($result, $bankCode)) {
                Log::info('Consent invalid (income check) — creating new consent', [
                    'user_id' => $userId,
                    'bank_code' => $bankCode,
                    'api_response' => $result,
                ]);

                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return $result;
        } catch (Exception $e) {
            Log::error('getIncomeCheckAdvanced exception caught', [
                'bank_id' => $bank->id ?? null,
                'user_id' => $userId,
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (stripos($e->getMessage(), 'Invalid Consent Data') !== false) {
                Log::info('Caught Invalid Consent Data exception (income check), attempting to create new consent.');
                $url = $this->createConsentAndGetUrl($userId, $bankCode);
                return $url ? ['redirect_url' => $url] : ['success' => false, 'payload' => ['message' => 'Invalid consent and failed to create new one.']];
            }

            return ['success' => false, 'payload' => ['message' => 'An error occurred while fetching income data.']];
        }
    }

    /**
     * Public route used by the bank redirect or for manual calling (preserves the behavior you had in the closure).
     */
    public function incomeCheckAdvanced($bankCode, $consentId): JsonResponse
    {
        // This is intentionally similar to the closure you provided — it directly returns service result.
        $fromDate = request()->query('fromDate'); // optional
        $toDate   = request()->query('toDate');   // optional
        $timeLine = request()->query('timeLine', 'byDay'); // optional
        $incomeCheck = filter_var(request()->query('incomeCheck', true), FILTER_VALIDATE_BOOLEAN);

        try {
            $result = $this->singleViewService->incomeCheckAdvanced(
                $bankCode,
                $consentId,
                $fromDate,
                $toDate,
                $timeLine,
                $incomeCheck
            );

            if (isset($result['redirect_url'])) {
                return response()->json(['success' => false, 'redirect_url' => $result['redirect_url']]);
            }

            return response()->json([
                'success' => true,
                'payload' => $result['payload'] ?? $result,
            ]);
        } catch (\Exception $e) {
            Log::error('incomeCheckAdvanced error', [
                'bankCode' => $bankCode,
                'consentId' => $consentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'payload' => ['message' => 'Failed to fetch income data.']
            ]);
        }
    }
}
