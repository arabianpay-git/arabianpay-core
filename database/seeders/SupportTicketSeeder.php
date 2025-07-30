<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        // Get random merchants and users to assign tickets to/from
        $merchants = User::where('user_type', 'merchant')->pluck('id')->toArray();
        $users = User::where('user_type', 'user')->pluck('id')->toArray();

        if (empty($merchants) || empty($users)) {
            $this->command->info('No merchants or customers found. Please seed users first.');
            return;
        }

        // Possible statuses (enum): 'active', 'solved', 'draft', 'canceled'
        $statuses = ['active', 'solved', 'draft', 'canceled'];

        // Create 50 support tickets
        for ($i = 1; $i <= 50; $i++) {
            $createdAt = Carbon::now()->subDays(rand(0, 60)); // created within last 60 days
            $status = $statuses[array_rand($statuses)];

            // For solved or canceled tickets, set resolution date after created_at
            $updatedAt = null;
            if (in_array($status, ['solved', 'canceled'])) {
                $updatedAt = (clone $createdAt)->addDays(rand(1, 10));
            } else {
                $updatedAt = $createdAt;
            }

            SupportTicket::create([
                'user_id' => $users[array_rand($users)],
                'assigned_to' => $merchants[array_rand($merchants)],
                'ticket_number' => strtoupper(Str::random(8)),
                'subject' => 'Issue with Order #' . rand(1000, 9999),
                'details' => 'Customer reported an issue with their order. Needs assistance.',
                'files' => [], // empty array, can be populated if needed
                'reply' => null,
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }
}
