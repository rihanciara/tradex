<?php

namespace Modules\AdvancedReports\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Demo Expense Data Seeder for Advanced Reports Module
 * 
 * Adds realistic expense transactions for demonstration purposes
 * in the Expense Monthly Report and other financial reports.
 * 
 * @package AdvancedReports
 * @author Horizonsoft Solutions
 * @version 1.1.0
 */
class DemoExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds for demo expense data
     *
     * @return void
     */
    public function run()
    {
        try {
            $business_id = 1; // Adjust if your business ID is different

            // Start transaction if not already in one
            if (DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $shouldCommit = true;
            } else {
                $shouldCommit = false;
            }

            // Get the first expense category or create a default one
            $expense_category = DB::table('expense_categories')->where('business_id', $business_id)->first();
        
        if (!$expense_category) {
            // Create demo expense categories
            $categories = [
                ['name' => 'Office Supplies', 'business_id' => $business_id, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Marketing & Advertising', 'business_id' => $business_id, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Utilities', 'business_id' => $business_id, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Travel & Transportation', 'business_id' => $business_id, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Professional Services', 'business_id' => $business_id, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Maintenance & Repairs', 'business_id' => $business_id, 'created_at' => now(), 'updated_at' => now()],
            ];
            
            DB::table('expense_categories')->insert($categories);
            $category_ids = DB::table('expense_categories')->where('business_id', $business_id)->pluck('id')->toArray();
        } else {
            $category_ids = DB::table('expense_categories')->where('business_id', $business_id)->pluck('id')->toArray();
        }
        
        // Get location ID
        $location_id = DB::table('business_locations')->where('business_id', $business_id)->first()->id ?? 1;
        
        // Generate demo expense transactions for the past 6 months
        $demo_expenses = [];
        $user_id = 1; // Adjust if needed
        
        for ($month = 0; $month < 6; $month++) {
            $date = Carbon::now()->subMonths($month);
            
            // Generate 8-15 expenses per month
            $expenses_per_month = rand(8, 15);
            
            for ($i = 0; $i < $expenses_per_month; $i++) {
                $category_id = $category_ids[array_rand($category_ids)];
                $category_name = DB::table('expense_categories')->where('id', $category_id)->value('name');
                
                // Generate realistic expense amounts based on category
                $amount = $this->getRealisticAmount($category_name);
                
                $expense_date = $date->copy()->addDays(rand(1, 28));
                
                $demo_expenses[] = [
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'type' => 'expense',
                    'status' => 'final',
                    'expense_category_id' => $category_id,
                    'ref_no' => 'EX' . $expense_date->format('Ym') . str_pad(($i + 1), 4, '0', STR_PAD_LEFT),
                    'transaction_date' => $expense_date->format('Y-m-d'),
                    'final_total' => $amount,
                    'total_before_tax' => $amount * 0.9, // Assuming some tax
                    'tax_amount' => $amount * 0.1,
                    'expense_for' => $user_id, // User ID who the expense is for
                    'additional_notes' => $this->getExpenseDescription($category_name) . ' - ' . $this->getExpenseNotes($category_name),
                    'created_by' => $user_id,
                    'created_at' => $expense_date,
                    'updated_at' => $expense_date,
                ];
            }
        }
        
            // Insert demo expenses
            if (!empty($demo_expenses)) {
                // Insert in chunks to avoid memory issues
                $chunks = array_chunk($demo_expenses, 50);
                foreach ($chunks as $chunk) {
                    DB::table('transactions')->insert($chunk);
                }

                $this->command->info('Created ' . count($demo_expenses) . ' demo expense transactions');
            }

            // Commit transaction if we started it
            if ($shouldCommit && DB::transactionLevel() > 0) {
                DB::commit();
            }

        } catch (\Exception $e) {
            // Rollback if we started the transaction
            if (isset($shouldCommit) && $shouldCommit && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('DemoExpenseSeeder error: ' . $e->getMessage());

            // Re-throw the exception if not in silent mode
            throw $e;
        }
    }
    
    /**
     * Get realistic expense amounts based on category
     */
    private function getRealisticAmount($category_name)
    {
        switch (strtolower($category_name)) {
            case 'office supplies':
                return rand(2500, 15000) / 100; // $25 - $150
                
            case 'marketing & advertising':
                return rand(10000, 50000) / 100; // $100 - $500
                
            case 'utilities':
                return rand(15000, 35000) / 100; // $150 - $350
                
            case 'travel & transportation':
                return rand(5000, 30000) / 100; // $50 - $300
                
            case 'professional services':
                return rand(20000, 100000) / 100; // $200 - $1000
                
            case 'maintenance & repairs':
                return rand(8000, 40000) / 100; // $80 - $400
                
            default:
                return rand(5000, 25000) / 100; // $50 - $250
        }
    }
    
    /**
     * Get realistic expense descriptions
     */
    private function getExpenseDescription($category_name)
    {
        $descriptions = [
            'office supplies' => ['Printer Paper & Ink', 'Stationery Items', 'Office Equipment', 'Cleaning Supplies'],
            'marketing & advertising' => ['Google Ads Campaign', 'Social Media Marketing', 'Print Advertisements', 'Website Maintenance'],
            'utilities' => ['Electricity Bill', 'Internet & Phone', 'Water Bill', 'Gas Bill'],
            'travel & transportation' => ['Business Trip', 'Fuel Expenses', 'Taxi/Uber', 'Parking Fees'],
            'professional services' => ['Legal Consultation', 'Accounting Services', 'IT Support', 'Business Consulting'],
            'maintenance & repairs' => ['Equipment Repair', 'Building Maintenance', 'Software Updates', 'Hardware Replacement'],
        ];
        
        $key = strtolower(str_replace(' & ', ' ', $category_name));
        $options = $descriptions[$key] ?? ['General Business Expense'];
        
        return $options[array_rand($options)];
    }
    
    /**
     * Get realistic expense notes
     */
    private function getExpenseNotes($category_name)
    {
        $notes = [
            'Monthly recurring expense',
            'One-time business purchase',
            'Essential business operation cost',
            'Approved by management',
            'Necessary for business growth',
            'Regular maintenance expense',
        ];
        
        return $notes[array_rand($notes)];
    }
}