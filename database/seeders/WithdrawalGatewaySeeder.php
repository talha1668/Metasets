<?php

namespace Database\Seeders;

use App\Models\WithdrawalGateway;
use Illuminate\Database\Seeder;

class WithdrawalGatewaySeeder extends Seeder
{
    public function run(): void
    {
        WithdrawalGateway::query()->delete();
        WithdrawalGateway::create([
            'name' => 'Bank Transfer',
            'currency' => 'USD',
            'rate' => 1,
            'min_amount' => 50.00,
            'max_amount' => 50000.00,
            'fixed_charge' => 5.00,
            'percent_charge' => 1.50,
            'description' => 'Direct bank transfer to your registered bank account. Processing time: 1-3 business days.',
            'parameters' => [
                [
                    'field_name' => 'bank_name',
                    'field_type' => 'text',
                    'field_label' => 'Bank Name',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your bank name',
                ],
                [
                    'field_name' => 'account_number',
                    'field_type' => 'text',
                    'field_label' => 'Account Number',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your account number',
                ],
            ],
            'status' => true,
        ]);
        WithdrawalGateway::create([
            'name' => 'PayPal',
            'currency' => 'USD',
            'rate' => 1,
            'min_amount' => 10.00,
            'max_amount' => 10000.00,
            'fixed_charge' => 0.30,
            'percent_charge' => 2.90,
            'description' => 'Instant withdrawal to your PayPal account. Funds available immediately.',
            'parameters' => [
                [
                    'field_name' => 'paypal_email',
                    'field_type' => 'email',
                    'field_label' => 'PayPal Email',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your PayPal email address',
                ],
                [
                    'field_name' => 'full_name',
                    'field_type' => 'text',
                    'field_label' => 'Full Name',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your full name',
                ],
            ],
            'status' => true,
        ]);
        WithdrawalGateway::create([
            'name' => 'USDT (Tether)',
            'currency' => 'USD',
            'rate' => 1,
            'min_amount' => 10.00,
            'max_amount' => 50000.00,
            'fixed_charge' => 1.00,
            'percent_charge' => 0.20,
            'description' => 'Withdraw USDT to your wallet. Multiple blockchain networks available.',
            'parameters' => [
                [
                    'field_name' => 'wallet_address',
                    'field_type' => 'text',
                    'field_label' => 'USDT Wallet Address',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your USDT wallet address',
                ],
            ],
            'status' => true,
        ]);
        WithdrawalGateway::create([
            'name' => 'Bitcoin (BTC)',
            'currency' => 'USD',
            'rate' => 1,
            'min_amount' => 20.00,
            'max_amount' => 100000.00,
            'fixed_charge' => 2.00,
            'percent_charge' => 0.50,
            'description' => 'Withdraw to your Bitcoin wallet. Network confirmation time varies based on blockchain congestion.',
            'parameters' => [
                [
                    'field_name' => 'btc_address',
                    'field_type' => 'text',
                    'field_label' => 'Bitcoin Wallet Address',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your BTC wallet address',
                ],
            ],
            'status' => true,
        ]);
    }
}
