<?php

namespace Modules\AdvancedReports\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdvancedReports\Entities\CustomerRecognitionSetting;
use Modules\AdvancedReports\Entities\AwardCatalog;
use Modules\AdvancedReports\Entities\CustomerEngagement;
use App\Business;
use App\Contact;
use App\User;
use Carbon\Carbon;

class CustomerRecognitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the first business (you can modify this to match your setup)
        $business = Business::first();
        
        if (!$business) {
            $this->command->info('No business found. Please create a business first.');
            return;
        }

        $business_id = $business->id;

        // 1. Create/Update Recognition Settings
        $settings = CustomerRecognitionSetting::updateOrCreate([
            'business_id' => $business_id
        ], [
            'weekly_enabled' => true,
            'monthly_enabled' => true,
            'yearly_enabled' => true,
            'winner_count_weekly' => 3,
            'winner_count_monthly' => 10,
            'winner_count_yearly' => 20,
            'scoring_method' => 'weighted',
            'sales_weight' => 0.7,
            'engagement_weight' => 0.3,
            'module_start_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
            'calculate_historical' => true,
            'historical_months' => 12,
            'is_active' => true
        ]);

        $this->command->info('Customer Recognition Settings created/updated');

        // 2. Create Sample Award Catalog Items
        $catalog_items = [
            [
                'award_name' => '$50 Gift Voucher',
                'description' => 'Redeemable gift voucher worth $50',
                'monetary_value' => 50.00,
                'point_threshold' => 20,
                'stock_required' => true,
                'stock_quantity' => 100,
                'is_active' => true
            ],
            [
                'award_name' => '$100 Gift Voucher',
                'description' => 'Premium gift voucher worth $100',
                'monetary_value' => 100.00,
                'point_threshold' => 50,
                'stock_required' => true,
                'stock_quantity' => 50,
                'is_active' => true
            ],
            [
                'award_name' => 'Certificate of Recognition',
                'description' => 'Digital certificate for top customer',
                'monetary_value' => 0.00,
                'point_threshold' => 0,
                'stock_required' => false,
                'stock_quantity' => 0,
                'is_active' => true
            ],
            [
                'award_name' => 'Premium Loyalty Badge',
                'description' => 'Special loyalty status with benefits',
                'monetary_value' => 25.00,
                'point_threshold' => 30,
                'stock_required' => false,
                'stock_quantity' => 0,
                'is_active' => true
            ],
            [
                'award_name' => '$200 Cash Prize',
                'description' => 'Cash prize for exceptional customers',
                'monetary_value' => 200.00,
                'point_threshold' => 100,
                'stock_required' => true,
                'stock_quantity' => 10,
                'is_active' => true
            ]
        ];

        foreach ($catalog_items as $item) {
            AwardCatalog::updateOrCreate([
                'business_id' => $business_id,
                'award_name' => $item['award_name']
            ], $item);
        }

        $this->command->info('Award Catalog items created: ' . count($catalog_items));

        // 3. Create Sample Customer Engagements (if customers exist)
        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->limit(10)
            ->get();

        if ($customers->count() > 0) {
            $engagement_types = [
                'youtube_follow' => 5,
                'facebook_follow' => 3,
                'instagram_follow' => 3,
                'google_review' => 10,
                'content_share' => 4,
                'referral' => 8
            ];

            $user = User::where('business_id', $business_id)->first();
            
            if ($user) {
                foreach ($customers as $customer) {
                    // Add 1-3 random engagements per customer
                    $engagement_count = rand(1, 3);
                    
                    for ($i = 0; $i < $engagement_count; $i++) {
                        $engagement_type = array_rand($engagement_types);
                        $points = $engagement_types[$engagement_type];
                        
                        CustomerEngagement::create([
                            'business_id' => $business_id,
                            'customer_id' => $customer->id,
                            'engagement_type' => $engagement_type,
                            'points' => $points,
                            'verification_notes' => 'Sample engagement for testing - ' . ucfirst(str_replace('_', ' ', $engagement_type)),
                            'platform' => $this->getPlatformForType($engagement_type),
                            'reference_url' => 'https://example.com/sample-link',
                            'recorded_by' => $user->id,
                            'recorded_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                            'status' => 'verified'
                        ]);
                    }
                }
                
                $this->command->info('Sample customer engagements created for ' . $customers->count() . ' customers');
            } else {
                $this->command->info('No users found to record engagements');
            }
        } else {
            $this->command->info('No customers found to create sample engagements');
        }

        $this->command->info('Customer Recognition System seeded successfully!');
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('1. Visit /advanced-reports/customer-recognition to view the system');
        $this->command->info('2. The system will calculate rankings based on your existing transaction data');
        $this->command->info('3. You can award customers and manage the catalog');
    }

    /**
     * Get platform name for engagement type
     */
    private function getPlatformForType($engagement_type)
    {
        $platforms = [
            'youtube_follow' => 'YouTube',
            'facebook_follow' => 'Facebook',
            'instagram_follow' => 'Instagram',
            'twitter_follow' => 'Twitter',
            'google_review' => 'Google',
            'content_share' => 'Social Media',
            'referral' => 'Word of Mouth'
        ];

        return $platforms[$engagement_type] ?? 'Social Media';
    }
}