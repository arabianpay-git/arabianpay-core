<?php

use App\Models\Approval;
use App\Models\Customer;
use App\Models\CustomerCreditLimit;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\CreditAssessmentService;
use App\Services\NotificationService;
use App\Services\RiskService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * General settings access
 */
if (! function_exists('get_setting')) {
    function get_setting(string $key, $default = null)
    {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}

/**
 * Tax setting
 */
if (! function_exists('get_tax')) {
    function get_tax($default = 0)
    {
        return get_setting('tax', $default);
    }
}

/**
 * Commission tax setting
 */
if (! function_exists('get_commission_tax')) {
    function get_commission_tax($default = 0)
    {
        return get_setting('commission_tax', $default);
    }
}

/**
 * Latest credit limit for a user
 */
if (! function_exists('get_credit_limit')) {
    function get_credit_limit($user_id, $default = 0.00)
    {
        $creditLimit = CustomerCreditLimit::where('user_id', $user_id)->latest()->first();
        return $creditLimit ? (float) $creditLimit->limit_arabianpay_after : (float) $default;
    }
}

/**
 * Latest seller commission for a user
 */
if (! function_exists('get_seller_commission')) {
    function get_seller_commission($user_id, $default = 5.00)
    {
        $commission = Approval::where('user_id', $user_id)->latest()->first();
        return $commission ? (float) $commission->commission : (float) $default;
    }
}

/**
 * System commission setting
 */
if (! function_exists('get_system_commission')) {
    function get_system_commission($default = 0)
    {
        return get_setting('system_commission', $default);
    }
}

/**
 * Calculate tax for an order object that has product_details JSON
 */
if (! function_exists('calculate_order_tax')) {
    function calculate_order_tax($order): float
    {
        $totalTax = 0.0;
        $items = json_decode($order->product_details ?? '[]', true) ?: [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            if (! $productId) {
                continue;
            }

            $product = Product::find($productId);
            if (! $product || ! isset($product->tax_type)) {
                continue;
            }

            $productTax = $product->tax ?? (float) get_setting('tax', 0);

            $attributePrice = collect($item['attributes'] ?? [])->sum(fn($a) => isset($a['price']) ? (float)$a['price'] : 0.0);
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
            $totalPrice = $attributePrice * $quantity;

            if ($product->tax_type === 'percent') {
                $tax = ($productTax / 100.0) * $totalPrice;
            } else {
                $tax = (float) $productTax * $quantity;
            }

            $totalTax += $tax;
        }

        return (float) $totalTax;
    }
}

/**
 * Map product details JSON into a Collection of objects:
 * ->product (Model), ->quantity, ->price, ->attributes, ->total
 */
if (! function_exists('map_product_details')) {
    function map_product_details(string $jsonDetails): Collection
    {
        $items = json_decode($jsonDetails ?: '[]', true) ?: [];

        return collect($items)
            ->map(function (array $item) {
                $productId = $item['product_id'] ?? null;
                if (! $productId) {
                    return null;
                }

                $product = Product::find($productId);
                if (! $product) {
                    return null;
                }

                $attributes = $item['attributes'] ?? [];
                $price = collect($attributes)->pluck('price')->filter()->first() ?: ($product->unit_price ?? 0);
                $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

                return (object) [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => (float) $price,
                    'attributes' => $attributes,
                    'total' => (float) $price * $quantity,
                ];
            })
            ->filter() // remove nulls
            ->values();
    }
}

/**
 * Current authenticated user (shortcut)
 */
if (! function_exists('currentUser')) {
    function currentUser(): ?User
    {
        return Auth::user();
    }
}

/**
 * Get employees list visible to the current user.
 * - Admin sees all (or filter by department if provided)
 * - Manager sees own department
 * - Regular employee sees only managers of their department
 */
if (! function_exists('getEmployees')) {
    function getEmployees($department = null)
    {
        $user = Auth::user();

        $query = User::query()->where('user_type', 'employee');

        if ($user) {
            // Exclude self always
            $query->where('id', '!=', $user->id);

            if ($user->user_type === 'admin') {
                if ($department !== null) {
                    $query->where('department_id', $department);
                }
            } elseif (! empty($user->is_manager)) {
                // Managers can see employees and managers in their department
                $query->where('department_id', $user->department_id);
            } else {
                // Non-manager employees see only managers in their department
                $query->where('department_id', $user->department_id)
                    ->where('is_manager', true);
            }
        } elseif ($department !== null) {
            $query->where('department_id', $department);
        }

        return $query->orderBy('first_name')->get();
    }
}

/**
 * Get credit score via service
 */
if (! function_exists('get_credit_score')) {
    function get_credit_score(int $userId): array
    {
        $service = app(CreditAssessmentService::class);
        $result = $service->assess($userId);
        return $result['creditScore'] ?? [];
    }
}

/**
 * Get risk score object for a given user id or User model.
 * Tries customer then merchant if customer missing.
 */
if (! function_exists('get_risk_score')) {
    function get_risk_score($userOrId)
    {
        $service = app(RiskService::class);

        if ($userOrId instanceof User) {
            $user = $userOrId;
        } else {
            $user = User::find($userOrId);
            if (! $user) {
                return null;
            }
        }

        // Prefer customer entity, fallback to merchant if present
        if ($user->customer) {
            return $service->analyzeCustomer($user->customer, 'customer');
        }

        if ($user->merchant) {
            return $service->analyzeCustomer($user->merchant, 'merchant');
        }

        return null;
    }
}

/**
 * Convert Hijri date to Gregorian using external API.
 * Accepts either a string "YYYY/MM/DD" or (year, month, day) integers.
 */
if (! function_exists('hijriToGregorian')) {
    function hijriToGregorian($hYearOrDate, ?int $hMonth = null, ?int $hDay = null, int $adjustment = 0): ?Carbon
    {
        if (is_string($hYearOrDate)) {
            $parts = array_map('intval', preg_split('/[\/\-\.]/', $hYearOrDate));
            if (count($parts) < 3) {
                return null;
            }
            [$hYear, $hMonth, $hDay] = $parts;
        } else {
            $hYear = (int) $hYearOrDate;
            if ($hMonth === null || $hDay === null) {
                return null; // require complete Y,M,D when passing integers
            }
        }

        $dateParam = sprintf('%02d-%02d-%04d', $hDay, $hMonth, $hYear);

        try {
            $response = Http::timeout(5)
                ->get('https://api.aladhan.com/v1/hToG', [
                    'date' => $dateParam,
                    'adjustment' => $adjustment,
                ]);
        } catch (\Throwable $e) {
            Log::warning('hijriToGregorian: http error: ' . $e->getMessage());
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json('data');
        $gregDate = $data['gregorian']['date'] ?? null;

        if (! $gregDate) {
            return null;
        }

        return Carbon::createFromFormat('d-m-Y', $gregDate);
    }
}

/**
 * Resolve media path to a fully-qualified URL.
 */
if (! function_exists('resolveMedia')) {
    function resolveMedia(?string $path, array $options = []): ?string
    {
        $options = array_merge([
            'type' => 'supplier',
            'default' => null,
            'partner_prefix' => null,
            'check_remote' => true,
            'http_timeout' => 2,
        ], $options);

        if (empty($path)) {
            return $options['default'];
        }

        // Absolute URL passed in
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $candidates = [];

        if ($options['type'] === 'product') {
            $candidates[] = public_path('uploads/' . ltrim($path, '/'));
        }

        $candidates[] = public_path('partners-media/' . ltrim($path, '/'));
        $candidates[] = public_path('storage/media/' . ltrim($path, '/'));
        $candidates[] = public_path(ltrim($path, '/'));

        foreach ($candidates as $fullPath) {
            if ($fullPath && file_exists($fullPath) && is_file($fullPath)) {
                $rel = str_replace('\\', '/', ltrim(str_replace(public_path(), '', $fullPath), '/'));
                $rel = $rel ?: ltrim($path, '/');
                return asset($rel);
            }
        }

        $prefix = $options['partner_prefix'] ?? rtrim('https://partners.arabianpay.net', '/');
        if ($options['type'] === 'product' && $options['partner_prefix'] === null) {
            $prefix = rtrim('https://partners.arabianpay.net/public', '/');
        }

        $partnerUrl = $prefix . '/' . ltrim($path, '/');

        if (! $options['check_remote']) {
            return $partnerUrl;
        }

        try {
            $response = Http::withOptions(['timeout' => (float) $options['http_timeout']])->head($partnerUrl);
            if ($response->successful()) {
                return $partnerUrl;
            }

            if (in_array($response->status(), [0, 403, 405])) {
                $response2 = Http::withOptions(['timeout' => (float) $options['http_timeout']])->get($partnerUrl);
                if ($response2->successful()) {
                    return $partnerUrl;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('resolveMedia: error checking partner URL: ' . $e->getMessage());
        }

        return $options['default'];
    }
}

/**
 * Backwards-compatible supplierMedia wrapper.
 */
if (! function_exists('supplierMedia')) {
    function supplierMedia(?string $path, ?string $defaultUrl = null): ?string
    {
        return resolveMedia($path, [
            'type' => 'supplier',
            'default' => $defaultUrl,
        ]);
    }
}

/**
 * Backwards-compatible productMedia wrapper.
 */
if (! function_exists('productMedia')) {
    function productMedia(?string $path, ?string $defaultUrl = null): ?string
    {
        return resolveMedia($path, [
            'type' => 'product',
            'default' => $defaultUrl,
        ]);
    }
}

/**
 * Simple translate wrapper that scopes keys to 'main.'
 */
if (! function_exists('translate')) {
    function translate($key, $replace = [], $locale = null)
    {
        if (! Str::startsWith($key, 'main.')) {
            $key = "main.$key";
        }

        return __($key, $replace, $locale);
    }
}

/**
 * Create notification via NotificationService
 */
if (! function_exists('create_notification')) {
    function create_notification(?int $userId, ?string $type, $data)
    {
        $service = app(NotificationService::class);
        return $service->createNotification($userId, $type, $data);
    }
}

/**
 * Get current user notifications (latest 20)
 */
if (! function_exists('current_user_notifications')) {
    function current_user_notifications()
    {
        if (! Auth::check()) {
            return collect();
        }

        return Notification::where('user_id', Auth::id())
            ->latest()
            ->take(20)
            ->get();
    }
}

/**
 * Get media URL with fallback to default image
 */
if (! function_exists('getMediaUrl')) {
    function getMediaUrl(string $filename, string $defaultImage = 'assets/media/images/default-image.png'): string
    {
        if (file_exists(public_path($filename))) {
            return asset($filename);
        }

        if (file_exists(public_path('partners-media/' . $filename))) {
            return asset('partners-media/' . $filename);
        }

        if (file_exists(public_path('storage/media/' . $filename))) {
            return asset('storage/media/' . $filename);
        }

        return asset($defaultImage);
    }
}

/**
 * Generate partner absolute route
 */
if (! function_exists('partnerRoute')) {
    function partnerRoute(string $name, array $parameters = []): string
    {
        $base = app()->environment('local') ? 'https://adminpanel.test' : 'https://partners.arabianpay.net';
        $path = route($name, $parameters, false);
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

/**
 * Settings helper that returns decoded JSON
 */
if (! function_exists('settings')) {
    function settings($key, $default = [])
    {
        $value = Setting::where('key', $key)->value('value');
        return $value ? json_decode($value, true) : $default;
    }
}

/**
 * Date format from general settings
 */
if (! function_exists('dateFormat')) {
    function dateFormat($includeTime = false): string
    {
        $general = settings('general', []);
        $dateFormat = $general['date_format'] ?? 'd M Y';

        if ($includeTime) {
            $timeFormat = isset($general['time_format']) && $general['time_format'] == '24' ? 'H:i' : 'h:i A';
            return $dateFormat . ' ' . $timeFormat;
        }

        return $dateFormat;
    }
}

/**
 * Update .env value (safe replace or append)
 */
if (! function_exists('updateEnvValue')) {
    function updateEnvValue(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return;
        }

        $envContents = file_get_contents($path);

        $escapedValue = str_replace('"', '\"', $value);

        if (preg_match("/^{$key}=.*$/m", $envContents)) {
            $envContents = preg_replace(
                "/^{$key}=.*$/m",
                $key . '="' . $escapedValue . '"',
                $envContents
            );
        } else {
            $envContents .= PHP_EOL . $key . '="' . $escapedValue . '"';
        }

        file_put_contents($path, $envContents);
    }
}

/**
 * Human readable number formatting: 1.2K, 2.5M, etc.
 */
if (! function_exists('human_number')) {
    function human_number(float $number): string
    {
        if ($number >= 1_000_000_000) return round($number / 1_000_000_000, 1) . 'B';
        if ($number >= 1_000_000)     return round($number / 1_000_000, 1) . 'M';
        if ($number >= 1_000)         return round($number / 1_000, 1) . 'K';
        return (string) number_format($number, 2, '.', '');
    }
}

/**
 * Check if user has sensitive permission
 */
if (! function_exists('hasSensitivePermission')) {
    function hasSensitivePermission(string $permission): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Manager employee → full access
        if ($user->user_type === 'employee' && !empty($user->is_manager)) {
            return true;
        }

        // Normalize stored permissions
        $raw = $user->sensitive_permissions ?? [];

        if (is_string($raw)) {
            $permissions = json_decode($raw, true) ?: [];
        } elseif (is_array($raw)) {
            $permissions = $raw;
        } elseif ($raw instanceof \Illuminate\Support\Collection) {
            $permissions = $raw->toArray();
        } else {
            $permissions = [];
        }

        return in_array($permission, $permissions, true);
    }
}

/**
 * Mask sensitive text
 */
if (! function_exists('maskedSensitiveText')) {
    function maskedSensitiveText(
        string $permission,
        ?string $text,
        int $startMask = 3,
        int $endMask = 3,
        ?int $maskLength = null,
        string $fallback = '***'
    ): string {
        if ($text === null || $text === '') {
            return $fallback;
        }

        // Permission allowed → return full text
        if (hasSensitivePermission($permission)) {
            return $text;
        }

        $length = Str::length($text);

        if ($length <= ($startMask + $endMask)) {
            return str_repeat('*', $maskLength ?? $length);
        }

        $start = Str::substr($text, 0, $startMask);
        $end   = Str::substr($text, -$endMask);

        $stars = $maskLength !== null
            ? str_repeat('*', $maskLength)
            : str_repeat('*', $length - ($startMask + $endMask));

        return $start . $stars . $end;
    }
}

/**
 * Authorize sensitive file or deny
 */
if (! function_exists('authorizeSensitiveFileOrDeny')) {
    function authorizeSensitiveFileOrDeny(
        string $permission,
        ?string $path,
        $fallback = 'Access Restricted'
    ) {
        if (! $path) {
            return $fallback;
        }

        // Permission allowed → return file path
        if (hasSensitivePermission($permission)) {
            return $path;
        }

        return $fallback;
    }
}
