<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class RiskManagementSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {

            $user = User::where('user_type', 'user')->inRandomOrder()->first();

            if (!$user) {
                continue;
            }

            DB::table('risk_management')->insert([
                'user_id' => $user->id,
                'creditor_id' => 'CRD' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'name' => 'Seller ' . $i,
                'registration_date' => Carbon::now()->subYears(rand(1, 5))->subDays(rand(1, 365)),
                'contact_email' => "seller{$i}@example.com",
                'contact_phone' => '00966' . rand(500000000, 599999999),
                'business_type' => ['Retail', 'Wholesale', 'Services'][rand(0, 2)],
                'kyc_status' => rand(0, 1),
                'compliance_status' => ['Pending', 'Approved', 'Rejected'][rand(0, 2)],

                'credit_score' => rand(300, 850),
                'default_count' => rand(0, 3),
                'outstanding_debt' => rand(1000, 100000),
                'payment_history' => json_encode([
                    'Jan' => rand(0, 1),
                    'Feb' => rand(0, 1),
                    'Mar' => rand(0, 1),
                    'Apr' => rand(0, 1),
                    'May' => rand(0, 1),
                    'Jun' => rand(0, 1)
                ]),
                'previous_enquiries' => json_encode([
                    'count' => rand(1, 10),
                    'last_enquiry' => Carbon::now()->subDays(rand(1, 180))->toDateString()
                ]),
                'credit_instruments' => json_encode([
                    'loans' => rand(0, 5),
                    'credit_cards' => rand(0, 3)
                ]),

                'total_transactions' => rand(50, 500),
                'avg_transaction_value' => rand(500, 5000),
                'last_transaction_date' => Carbon::now()->subDays(rand(1, 90)),
                'dispute_rate' => rand(0, 10) / 10,
                'account_activity_score' => rand(50, 100),

                'risk_score' => rand(1, 100) / 1.0,
                'risk_level' => ['Low', 'Medium', 'High'][rand(0, 2)],
                'flagged_reasons' => json_encode(['Late Payment', 'High Debt', 'Inactive Account']),
                'manual_review_required' => rand(0, 1),

                'created_at' => now(),
                'updated_at' => now(),
                'last_risk_assessment' => Carbon::now()->subDays(rand(1, 60)),
                'simah_api_response' => json_encode(['status' => 'OK', 'code' => 200]),
                'external_credit_data' => json_encode(['bureau' => 'SIMAH', 'score' => rand(400, 800)]),
            ]);
        }
    }
}
