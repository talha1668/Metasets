<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        PaymentGateway::truncate();
        PaymentGateway::create([
            'name' => 'NowPayments',
            'slug' => 'nowpayments',
            'type' => 'automatic',
            'currency' => 'USD',
            'rate' => 1,
            'min_amount' => 1.00,
            'max_amount' => 100000.00,
            'fixed_charge' => 0.00,
            'percent_charge' => 0.50,
            'description' => 'Pay with cryptocurrency via NowPayments (BTC, ETH, USDT, and 300+ coins)',
            'credentials' => [
                'api_key' => '668EP5E-7B0M9X6-GXNTQ5Z-8CQFD3M',
                'ipn_secret' => 'Uc22733ESZqnxY12jU6XduR4bIrrzPQU',
            ],
            'parameters' => null,
            'status' => true,
            'sort_order' => 2,
        ]);
        PaymentGateway::create([
            'name' => 'Bank Transfer',
            'slug' => 'bank-transfer',
            'type' => 'manual',
            'currency' => 'USD',
            'file' => 'gateways/bank-transfer.png',
            'rate' => 1.00,
            'min_amount' => 10.00,
            'max_amount' => 50000.00,
            'fixed_charge' => 0.00,
            'percent_charge' => 0.00,
            'description' => 'Transfer funds directly to our bank account',
            'credentials' => [
                'bank_name' => 'Global Commercial Bank',
                'account_name' => 'YourCompany Ltd',
                'account_number' => '1234567890',
            ],
            'parameters' => [
                [
                    'field_name' => 'transaction_id',
                    'field_label' => 'Transaction ID',
                    'field_type' => 'text',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your transaction ID',
                ],
                [
                    'field_name' => 'sender_account',
                    'field_label' => 'Sender Account Number',
                    'field_type' => 'text',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your account number',
                ],
                [
                    'field_name' => 'proof_file',
                    'field_label' => 'Payment Proof',
                    'field_type' => 'file',
                    'field_required' => true,
                    'field_placeholder' => 'Upload payment receipt',
                ],
            ],
            'status' => true,
            'sort_order' => 1,
        ]);

        PaymentGateway::create([
            'name' => 'Bitcoin',
            'slug' => 'bitcoin',
            'type' => 'manual',
            'currency' => 'BTC',
            'file' => 'gateways/bitcoin.png',
            'rate' => 45000.00,
            'min_amount' => 0.0001,
            'max_amount' => 10.0,
            'fixed_charge' => 0.00,
            'percent_charge' => 1.00,
            'description' => 'Pay with Bitcoin cryptocurrency',
            'credentials' => [
                'wallet_address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
                'network' => 'Bitcoin Mainnet'
            ],
            'parameters' => [
                [
                    'field_name' => 'transaction_hash',
                    'field_label' => 'Transaction Hash',
                    'field_type' => 'text',
                    'field_required' => true,
                    'field_placeholder' => 'Enter Bitcoin transaction hash',
                ],
                [
                    'field_name' => 'sender_wallet',
                    'field_label' => 'Your Wallet Address',
                    'field_type' => 'text',
                    'field_required' => true,
                    'field_placeholder' => 'Enter your Bitcoin address',
                ],
                [
                    'field_name' => 'proof_file',
                    'field_label' => 'Transaction Screenshot',
                    'field_type' => 'file',
                    'field_required' => false,
                    'field_placeholder' => 'Upload screenshot (optional)',
                ],
            ],
            'status' => true,
            'sort_order' => 2,
        ]);

        PaymentGateway::create([
            'name' => 'Ethereum',
            'slug' => 'ethereum',
            'type' => 'manual',
            'currency' => 'ETH',
            'file' => 'gateways/ethereum.png',
            'rate' => 2500.00,
            'min_amount' => 1,
            'max_amount' => 100.0,
            'fixed_charge' => 0.00,
            'percent_charge' => 1.00,
            'description' => 'Pay with Ethereum cryptocurrency',
            'credentials' => [
                'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb',
                'network' => 'Ethereum (ERC-20)'
            ],
            'parameters' => [
                [
                    'field_name' => 'transaction_hash',
                    'field_label' => 'Transaction Hash',
                    'field_type' => 'text',
                    'field_required' => true,
                    'field_placeholder' => 'Enter Ethereum transaction hash',
                ],
                [
                    'field_name' => 'proof_file',
                    'field_label' => 'Transaction Screenshot',
                    'field_type' => 'file',
                    'field_required' => false,
                    'field_placeholder' => 'Upload screenshot (optional)',
                ],
            ],
            'status' => true,
            'sort_order' => 3,
        ]);

        PaymentGateway::create([
            'name' => 'USDT (ERC-20)',
            'slug' => 'usdt-erc20',
            'type' => 'manual',
            'currency' => 'USDT',
            'file' => 'gateways/usdt-erc20.png',
            'rate' => 1.00,
            'min_amount' => 10.00,
            'max_amount' => 100000.00,
            'fixed_charge' => 0.00,
            'percent_charge' => 0.50,
            'description' => 'Pay with Ethereum cryptocurrency and Network: Ethereum (ERC-20)',
            'credentials' => [
                'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb',
                'network' => 'Ethereum (ERC-20)'
            ],
            'parameters' => [
                [
                    'field_name' => 'transaction_hash',
                    'field_label' => 'Transaction Hash',
                    'field_type' => 'text',
                    'field_required' => true,
                    'field_placeholder' => 'Enter USDT transaction hash',
                ],
                [
                    'field_name' => 'proof_file',
                    'field_label' => 'Transaction Screenshot',
                    'field_type' => 'file',
                    'field_required' => false,
                    'field_placeholder' => 'Upload screenshot (optional)',
                ],
            ],
            'status' => true,
            'sort_order' => 4,
        ]);
    }
}
