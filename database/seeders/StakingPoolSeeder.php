<?php

namespace Database\Seeders;

use App\Models\StakingPool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StakingPoolSeeder extends Seeder
{
    public function run(): void
    {
        StakingPool::truncate();
        StakingPool::create([
            'name' => 'Bitcoin Standard Pool',
            'token_symbol' => 'BTC',
            'description' => 'Stake your Bitcoin and earn competitive returns. Our Bitcoin staking pool offers secure and reliable rewards with flexible lock periods. Perfect for long-term Bitcoin holders looking to maximize their holdings.',
            'min_stake_amount' => 0.001,
            'max_stake_amount' => 10.0,
            'apy_rate' => 4.5,
            'lock_days' => 90,
            'total_pool_size' => 100.0,
            'current_staked' => 0.0,
            'status' => 'active',
            'auto_compound' => false,
        ]);

        StakingPool::create([
            'name' => 'Ethereum Flexible Staking',
            'token_symbol' => 'ETH',
            'description' => 'Flexible Ethereum staking with daily rewards. Stake ETH with a shorter lock period and enjoy consistent returns. Ideal for users who want flexibility while earning passive income.',
            'min_stake_amount' => 0.01,
            'max_stake_amount' => 100.0,
            'apy_rate' => 5.2,
            'lock_days' => 30,
            'total_pool_size' => 1000.0,
            'current_staked' => 0.0,
            'status' => 'active',
            'auto_compound' => true,
        ]);

        StakingPool::create([
            'name' => 'USDT Express Pool',
            'token_symbol' => 'USDT',
            'description' => 'Quick turnaround USDT staking with competitive returns. 15-day lock period makes this perfect for short-term investors. Get your rewards faster while maintaining good APY.',
            'min_stake_amount' => 50.0,
            'max_stake_amount' => 50000.0,
            'apy_rate' => 6.0,
            'lock_days' => 15,
            'total_pool_size' => 500000.0,
            'current_staked' => 0.0,
            'status' => 'active',
            'auto_compound' => false,
        ]);

        StakingPool::create([
            'name' => 'Cardano Long-term Staking',
            'token_symbol' => 'ADA',
            'description' => 'Long-term Cardano staking with attractive rewards. Support the Cardano network while earning passive income. 120-day commitment for optimal returns.',
            'min_stake_amount' => 100.0,
            'max_stake_amount' => 50000.0,
            'apy_rate' => 5.8,
            'lock_days' => 120,
            'total_pool_size' => 500000.0,
            'current_staked' => 0.0,
            'status' => 'active',
            'auto_compound' => true,
        ]);

        StakingPool::create([
            'name' => 'Polygon Network Staking',
            'token_symbol' => 'MATIC',
            'description' => 'Stake MATIC tokens and support the Polygon network. Flexible 45-day lock period with competitive returns. Perfect for DeFi enthusiasts.',
            'min_stake_amount' => 50.0,
            'max_stake_amount' => 25000.0,
            'apy_rate' => 7.5,
            'lock_days' => 45,
            'total_pool_size' => 250000.0,
            'current_staked' => 0.0,
            'status' => 'active',
            'auto_compound' => false,
        ]);


        StakingPool::create([
            'name' => 'USDC Conservative Pool',
            'token_symbol' => 'USDC',
            'description' => 'Conservative staking option for USDC holders. Regulated and secure stablecoin staking with predictable returns. Great for portfolio diversification.',
            'min_stake_amount' => 100.0,
            'max_stake_amount' => 75000.0,
            'apy_rate' => 7.0,
            'lock_days' => 60,
            'total_pool_size' => 750000.0,
            'current_staked' => 0.0,
            'status' => 'active',
            'auto_compound' => false,
        ]);
    }
}
