<?php

namespace Modules\AdvancedReports\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\AdvancedReports\Entities\StaffAward;
use Modules\AdvancedReports\Entities\StaffPerformanceActivity;
use Modules\AdvancedReports\Entities\StaffAwardPeriod;
use App\User;
use App\Business;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffRecognitionDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Get the first business that has service_staff enabled
        $business = Business::whereRaw("JSON_CONTAINS(enabled_modules, '\"service_staff\"')")
            ->first();
            
        if (!$business) {
            // Enable service_staff for the first business if none found
            $business = Business::first();
            if ($business) {
                $enabled_modules = json_decode($business->enabled_modules, true) ?: [];
                if (!in_array('service_staff', $enabled_modules)) {
                    $enabled_modules[] = 'service_staff';
                    $business->enabled_modules = json_encode($enabled_modules);
                    $business->save();
                }
            } else {
                $this->command->error('No business found. Please create a business first.');
                return;
            }
        }

        // Get or create service staff role and users
        $serviceStaffRole = $this->createServiceStaffRole($business->id);
        $serviceStaff = $this->createServiceStaff($business->id, $serviceStaffRole);

        // Create award periods
        $awardPeriods = $this->createAwardPeriods($business->id);

        // Create performance activities
        $this->createPerformanceActivities($business->id, $serviceStaff);

        // Create staff awards
        $this->createStaffAwards($business->id, $serviceStaff, $awardPeriods);

        $this->command->info('Service Staff Recognition demo data created successfully!');
    }

    /**
     * Create or get service staff role
     */
    private function createServiceStaffRole($businessId)
    {
        // Check if a service staff role already exists
        $serviceStaffRole = Role::where('business_id', $businessId)
            ->where('is_service_staff', 1)
            ->first();

        if (!$serviceStaffRole) {
            // Create a new service staff role
            $serviceStaffRole = Role::create([
                'name' => 'Service Staff#' . $businessId,
                'business_id' => $businessId,
                'is_service_staff' => 1,
            ]);

            $this->command->info('Created Service Staff role: ' . $serviceStaffRole->name);
        } else {
            $this->command->info('Using existing Service Staff role: ' . $serviceStaffRole->name);
        }

        return $serviceStaffRole;
    }

    /**
     * Create or get service staff users
     */
    private function createServiceStaff($businessId, $serviceStaffRole)
    {
        $staffData = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'username' => 'sarah.johnson',
                'email' => 'sarah.johnson@example.com',
                'password' => bcrypt('password123'),
                'language' => 'en',
                'status' => 'active',
                'allow_login' => 1,
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Chen',
                'username' => 'michael.chen',
                'email' => 'michael.chen@example.com',
                'password' => bcrypt('password123'),
                'language' => 'en',
                'status' => 'active',
                'allow_login' => 1,
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Rodriguez',
                'username' => 'emily.rodriguez',
                'email' => 'emily.rodriguez@example.com',
                'password' => bcrypt('password123'),
                'language' => 'en',
                'status' => 'active',
                'allow_login' => 1,
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Thompson',
                'username' => 'david.thompson',
                'email' => 'david.thompson@example.com',
                'password' => bcrypt('password123'),
                'language' => 'en',
                'status' => 'active',
                'allow_login' => 1,
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Wilson',
                'username' => 'lisa.wilson',
                'email' => 'lisa.wilson@example.com',
                'password' => bcrypt('password123'),
                'language' => 'en',
                'status' => 'active',
                'allow_login' => 1,
            ]
        ];

        $serviceStaff = [];
        foreach ($staffData as $staff) {
            // Check if user already exists
            $existingUser = User::where('email', $staff['email'])->first();
            
            if (!$existingUser) {
                // Create new user with business_id
                $staff['business_id'] = $businessId;
                $user = User::create($staff);
                
                // Assign service staff role
                $user->assignRole($serviceStaffRole->name);
                
                $serviceStaff[] = $user;
                $this->command->info('Created service staff: ' . $user->first_name . ' ' . $user->last_name);
            } else {
                // Ensure existing user has service staff role
                if (!$existingUser->hasRole($serviceStaffRole->name)) {
                    $existingUser->assignRole($serviceStaffRole->name);
                    $this->command->info('Assigned service staff role to existing user: ' . $existingUser->first_name . ' ' . $existingUser->last_name);
                }
                $serviceStaff[] = $existingUser;
            }
        }

        return $serviceStaff;
    }

    /**
     * Create award periods
     */
    private function createAwardPeriods($businessId)
    {
        $periods = [];
        
        // Create periods for last 3 months
        for ($i = 2; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();
            
            $period = StaffAwardPeriod::create([
                'business_id' => $businessId,
                'period_type' => 'monthly',
                'period_start' => $startDate,
                'period_end' => $endDate,
                'is_finalized' => $i > 0, // Finalize past periods
                'winner_count' => 3,
                'created_at' => $startDate,
                'updated_at' => $startDate,
            ]);
            
            $periods[] = $period;
        }

        return $periods;
    }

    /**
     * Create performance activities
     */
    private function createPerformanceActivities($businessId, $serviceStaff)
    {
        $activityTypes = [
            'punctuality' => ['points' => [8, 10, 12], 'descriptions' => [
                'Arrived 15 minutes early and helped set up',
                'Perfect attendance this week',
                'Always on time, sets great example'
            ]],
            'customer_service' => ['points' => [15, 20, 25], 'descriptions' => [
                'Excellent customer feedback received',
                'Handled difficult customer situation professionally',
                'Customer specifically requested this staff member'
            ]],
            'upselling' => ['points' => [10, 15, 20], 'descriptions' => [
                'Successfully upsold premium service',
                'Increased average order value by 20%',
                'Excellent product knowledge demonstration'
            ]],
            'teamwork' => ['points' => [12, 15, 18], 'descriptions' => [
                'Helped train new team member',
                'Excellent collaboration on busy shift',
                'Always willing to help colleagues'
            ]],
            'training_completion' => ['points' => [20, 25, 30], 'descriptions' => [
                'Completed advanced service training',
                'Passed certification with excellent score',
                'Led training session for team'
            ]],
            'cleanliness' => ['points' => [8, 10, 12], 'descriptions' => [
                'Maintained exceptional workspace cleanliness',
                'Noticed and resolved safety issue',
                'Excellent hygiene standards maintained'
            ]]
        ];

        // Create activities for each staff member over the past 3 months
        foreach ($serviceStaff as $staff) {
            // Create 15-25 random activities per staff member
            $activityCount = rand(15, 25);
            
            for ($i = 0; $i < $activityCount; $i++) {
                $activityType = array_rand($activityTypes);
                $typeData = $activityTypes[$activityType];
                
                $points = $typeData['points'][array_rand($typeData['points'])];
                $description = $typeData['descriptions'][array_rand($typeData['descriptions'])];
                
                // Random date within last 3 months
                $recordedDate = Carbon::now()->subDays(rand(1, 90));
                
                StaffPerformanceActivity::create([
                    'business_id' => $businessId,
                    'staff_id' => $staff->id,
                    'activity_type' => $activityType,
                    'description' => $description,
                    'points' => $points,
                    'recorded_by' => 1, // Admin user
                    'recorded_date' => $recordedDate,
                    'status' => 'verified',
                    'verification_notes' => 'Verified by manager observation',
                    'created_at' => $recordedDate,
                    'updated_at' => $recordedDate,
                ]);
            }
        }

        $this->command->info('Created performance activities for ' . count($serviceStaff) . ' staff members');
    }

    /**
     * Create staff awards
     */
    private function createStaffAwards($businessId, $serviceStaff, $awardPeriods)
    {
        $manualAwards = [
            ['description' => '$50 Gift Card', 'value' => 50.00],
            ['description' => '$30 Lunch Voucher', 'value' => 30.00],
            ['description' => '$25 Coffee Shop Gift Card', 'value' => 25.00],
            ['description' => '$40 Retail Store Voucher', 'value' => 40.00],
            ['description' => '$20 Movie Theater Gift Card', 'value' => 20.00],
            ['description' => '$35 Restaurant Gift Certificate', 'value' => 35.00],
        ];

        foreach ($awardPeriods as $index => $period) {
            if ($period->is_finalized) {
                // Award top 3 performers for finalized periods
                $topStaff = collect($serviceStaff)->shuffle()->take(3);
                
                $rank = 1;
                foreach ($topStaff as $staff) {
                    $award = $manualAwards[array_rand($manualAwards)];
                    
                    StaffAward::create([
                        'business_id' => $businessId,
                        'staff_id' => $staff->id,
                        'period_id' => $period->id,
                        'period_type' => 'monthly',
                        'period_start' => $period->period_start,
                        'period_end' => $period->period_end,
                        'award_type' => 'manual',
                        'gift_description' => $award['description'],
                        'gift_monetary_value' => $award['value'],
                        'award_notes' => $rank == 1 ? 'Top performer of the month!' : 
                                      ($rank == 2 ? 'Excellent performance!' : 'Great job this month!'),
                        'rank_position' => $rank,
                        'sales_total' => rand(5000, 25000),
                        'transaction_count' => rand(50, 200),
                        'performance_points' => rand(80, 150),
                        'final_score' => rand(85, 95),
                        'is_awarded' => 1,
                        'awarded_by' => 1,
                        'awarded_date' => Carbon::parse($period->period_end)->addDays(2),
                        'created_at' => Carbon::parse($period->period_end)->addDays(2),
                        'updated_at' => Carbon::parse($period->period_end)->addDays(2),
                    ]);
                    
                    $rank++;
                }
            }
        }

        $this->command->info('Created staff awards for finalized periods');
    }
}