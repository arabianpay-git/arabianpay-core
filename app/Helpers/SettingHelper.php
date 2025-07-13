<?php

use App\Models\Approval;
use App\Models\CustomerCreditLimit;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

// Get Settings
if (!function_exists('get_setting')) {
    function get_setting($label, $default = null)
    {
        $setting = Setting::where('label', $label)->first();
        return $setting ? $setting->value : $default;
    }
}

// Get Tax setting
if (!function_exists('get_tax')) {
    function get_tax($default = 0)
    {
        return get_setting('tax', $default);
    }
}

// Get Commission Tax setting
if (!function_exists('get_commission_tax')) {
    function get_commission_tax($default = 0)
    {
        return get_setting('commission_tax', $default);
    }
}

if (!function_exists('get_credit_limit')) {
    function get_credit_limit($user_id, $default = 0.00)
    {
        $creditLimit = CustomerCreditLimit::where('user_id', $user_id)->latest()->first();
        return $creditLimit ? $creditLimit->limit_arabianpay_after : $default;
    }
}

if (!function_exists('get_seller_commission')) {
    function get_seller_commission($user_id, $default = 5.00)
    {
        $commission = Approval::where('user_id', $user_id)->latest()->first();
        return $commission ? $commission->comission : $default;
    }
}

if (!function_exists('get_system_commission')) {
    function get_system_commission($default = 0)
    {
        return get_setting('system_commission', $default);
    }
}


if (!function_exists('calculate_order_tax')) {
    function calculate_order_tax($order)
    {
        $totalTax = 0;
        $items = json_decode($order->product_details, true);

        foreach ($items as $item) {
            $product = App\Models\Product::find($item['product_id']);

            if (!$product || !isset($product->tax_type)) {
                continue;
            }

            // Get tax value, use setting fallback if null
            $productTax = $product->tax ?? get_setting('tax', 0);

            // Calculate item total price (sum attribute prices × quantity)
            $attributePrice = collect($item['attributes'])->sum('price');
            $totalPrice = $attributePrice * $item['quantity'];

            if ($product->tax_type === 'percent') {
                $tax = ($productTax / 100) * $totalPrice;
            } else {
                $tax = $productTax * $item['quantity'];
            }

            $totalTax += $tax;
        }

        return $totalTax;
    }
}

if (! function_exists('map_product_details')) {
    function map_product_details(string $jsonDetails): Collection
    {
        $items = json_decode($jsonDetails, true) ?: [];

        return collect($items)->map(function (array $item) {
            // find product
            $product = Product::find($item['product_id']);

            if ($product) {
                // price: first attribute price or fallback to unit_price
                $price = collect($item['attributes'] ?? [])
                    ->pluck('price')
                    ->first()
                    ?: $product->unit_price
                    ?: 0;

                $quantity = $item['quantity'] ?? 0;

                return (object)[
                    'product'    => $product,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'attributes' => $item['attributes'] ?? [],
                    'total'      => $price * $quantity,
                ];
            }
        });
    }
}

if (!function_exists('currentUser')) {
    function currentUser()
    {
        return Auth::user();
    }
}


if (!function_exists('getEmployees')) {
    function getEmployees($department = null)
    {
        $user = Auth::user();

        $query = User::where('user_type', 'employee');

        if ($user) {
            // Exclude self
            $query->where('id', '!=', $user->id);

            if ($user->user_type === 'admin') {
                // Admin can see all, or filter by department if given
                if ($department !== null) {
                    $query->where('department_id', $department);
                }
            } elseif ($user->is_manager) {

                $query->where(function ($q) use ($user) {
                    $q->where('department_id', $user->department_id)
                        ->where(function ($q2) {
                            $q2->where('is_manager', true) // other managers
                                ->orWhere('user_type', 'admin'); // or admins
                        })
                        ->orWhere(function ($q3) use ($user) {
                            $q3->where('department_id', $user->department_id)
                                ->where('is_manager', false); // employees
                        });
                });
            } else {

                $query->where('department_id', $user->department_id)
                    ->where('is_manager', true);
            }
        }

        return $query->orderBy('first_name')->get();
    }
}

use App\Services\CreditAssessmentService;

if (! function_exists('get_credit_score')) {
    /**
     * Get credit score for a given user id.
     *
     * @param int $userId
     * @return array
     */
    function get_credit_score(int $userId): array
    {
        $service = new CreditAssessmentService();
        $result = $service->assess($userId);
        return $result['creditScore'] ?? [];
    }
}

use App\Services\RiskAnalyticsService;

/**
 * Get risk score object for a given user or user ID
 *
 * @param int|User $userOrId
 * @return object|null
 */
function get_risk_score($userOrId)
{
    $service = app(RiskAnalyticsService::class);

    if ($userOrId instanceof User) {
        $user = $userOrId;
    } else {
        $user = User::find($userOrId);
        if (!$user) {
            return null;
        }
    }

    return $service->calculateForUser($user);
}

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;  // ← ADDED
use Illuminate\Support\Facades\Log;

/**
 * Convert a Hijri (Islamic) date to a Gregorian date via Aladhan API.
 *
 * Supports either:
 *   hijriToGregorian(1446, 1, 9)
 * or
 *   hijriToGregorian('1446/01/09')
 *
 * @param  int|string  $hYearOrDate  Hijri year or date string "YYYY/MM/DD"
 * @param  int|null    $hMonth       Hijri month (1–12)
 * @param  int|null    $hDay         Hijri day (1–30)
 * @param  int         $adjustment   Optional day-adjustment for Hijri date
 * @return \Carbon\Carbon|null       Gregorian Carbon date or null on failure
 */
if (! function_exists('hijriToGregorian')) {
    function hijriToGregorian($hYearOrDate, int $hMonth = null, int $hDay = null, int $adjustment = 0): ?Carbon
    {
        // parse "YYYY/MM/DD" string if given
        if (is_string($hYearOrDate)) {
            [$hYear, $hMonth, $hDay] = array_map('intval', explode('/', $hYearOrDate));
        } else {
            $hYear = $hYearOrDate;
        }

        // build API date in DD-MM-YYYY
        $dateParam = sprintf('%02d-%02d-%04d', $hDay, $hMonth, $hYear);

        // call Aladhan API
        $response = Http::timeout(5)
            ->get('https://api.aladhan.com/v1/hToG', [
                'date'       => $dateParam,
                'adjustment' => $adjustment,
            ]);

        if (! $response->successful()) {
            return null; // or throw exception if you prefer
        }

        $data = $response->json('data');

        // grab the gregorian date string "DD-MM-YYYY"
        $gregDate = $data['gregorian']['date'] ?? null;

        if (! $gregDate) {
            return null;
        }

        return Carbon::createFromFormat('d-m-Y', $gregDate);
    }
}

if (! function_exists('supplierMedia')) {
    /**
     * Prefix the given path with https://partners.arabianpay.net if not already prefixed,
     * return default URL if path is empty or null.
     *
     * @param string|null $path
     * @param string|null $defaultUrl Optional default URL if path is missing
     * @return string|null
     */
    function supplierMedia(?string $path, ?string $defaultUrl = null): ?string
    {
        if (!$path) {
            return $defaultUrl;
        }

        $prefix = 'https://partners.arabianpay.net';

        if (str_starts_with($path, 'http')) {
            // Already a full URL (any domain), return as is
            return $path;
        }

        return $prefix . $path;
    }
}

if (!function_exists('translate')) {
    function translate($key, $replace = [], $locale = null)
    {
        if (!str_starts_with($key, 'main.')) {
            $key = "main.$key";
        }

        return __($key, $replace, $locale);
    }
}

use App\Services\NotificationService;

if (!function_exists('create_notification')) {
    /**
     * Create a notification easily.
     *
     * @param int|null $userId
     * @param string|null $type
     * @param string|array $data
     * @return \App\Models\Notification
     */
    function create_notification(?int $userId, ?string $type, $data)
    {
        $service = app(NotificationService::class);
        return $service->createNotification($userId, $type, $data);
    }
}

use App\Models\Notification;

if (!function_exists('current_user_notifications')) {
    function current_user_notifications()
    {
        if (!Auth::check()) {
            return collect();
        }

        return Notification::where('user_id', Auth::id())
            ->latest()
            ->take(20)
            ->get();
    }
}
