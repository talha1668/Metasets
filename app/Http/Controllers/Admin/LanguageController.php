<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function index(Request $request): Response
    {
        try {
            $query = Language::withCount('translations')
                ->orderBy('is_default', 'desc')
                ->orderBy('name');

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('native_name', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->filled('status')) {
                $status = $request->get('status');
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                } elseif ($status === 'default') {
                    $query->where('is_default', true);
                }
            }

            $languages = $query->get();

            // Calculate stats
            $allLanguages = Language::withCount('translations')->get();
            $totalKeys = Translation::distinct('key')->count('key');

            return Inertia::render('Admin/Language/Index', [
                'languages' => $languages,
                'filters' => [
                    'search' => $request->get('search', ''),
                    'status' => $request->get('status', ''),
                ],
                'stats' => [
                    'total_languages' => $allLanguages->count(),
                    'active_languages' => $allLanguages->where('is_active', true)->count(),
                    'total_keys' => $totalKeys,
                ],
            ]);

        } catch (\Exception $e) {
            return Inertia::render('Admin/Language/Index', [
                'languages' => [],
                'filters' => [
                    'search' => '',
                    'status' => '',
                ],
                'stats' => [
                    'total_languages' => 0,
                    'active_languages' => 0,
                    'total_keys' => 0,
                ],
                'error' => 'Unable to load languages. Please try again.'
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:languages,code',
            'name' => 'required|string|max:255',
            'native_name' => 'required|string|max:255',
            'flag' => 'required|string|max:10',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            Cache::forget('frontend_languages');
            $language = Language::create($request->all());
            $this->createBaseTranslations($language);
            return redirect()->back()->with('success', 'Language created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create language. Please try again.']);
        }
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:languages,code,' . $language->id,
            'name' => 'required|string|max:255',
            'native_name' => 'required|string|max:255',
            'flag' => 'required|string|max:10',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            Cache::forget('frontend_languages');
            $language->update($request->all());
            return redirect()->back()->with('success', 'Language updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to update language. Please try again.']);
        }
    }

    public function destroy(Language $language): RedirectResponse
    {
        try {
            if ($language->is_default) {
                return redirect()->back()->withErrors(['error' => 'Cannot delete default language.']);
            }

            $language->translations()->delete();
            $language->delete();

            return redirect()->back()->with('success', 'Language deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to delete language. Please try again.']);
        }
    }

    public function translations(Request $request, Language $language): Response
    {
        try {
            $search = $request->get('search', '');
            $status = $request->get('status', 'all');
            $component = $request->get('component', 'general');
            $perPage = $request->get('per_page', 20);

            $query = Translation::where('language_id', $language->id);
            if ($component === 'dynamic') {
                $query->where('is_dynamic', 1);
            } else {
                $componentKeys = $this->getComponentKeys($component);
                if (!empty($componentKeys)) {
                    $query->whereIn('key', $componentKeys);
                } else {
                    $query->where('id', 0);
                }
            }

            if ($search && trim($search) !== '') {
                $searchTerm = trim($search);
                $query->where(function($q) use ($searchTerm) {
                    $q->where('key', 'like', "%{$searchTerm}%")
                        ->orWhere('value', 'like', "%{$searchTerm}%");
                });
            }

            if ($status !== 'all') {
                if ($status === 'translated') {
                    $query->whereNotNull('value')
                        ->where('value', '!=', '');
                } elseif ($status === 'untranslated') {
                    $query->where(function($q) {
                        $q->whereNull('value')
                            ->orWhere('value', '=', '');
                    });
                }
            }

            $translations = $query->orderBy('key')->paginate($perPage);

            // Calculate stats for this language
            $totalTranslations = Translation::where('language_id', $language->id)->count();
            $translatedCount = Translation::where('language_id', $language->id)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->count();
            $missingCount = $totalTranslations - $translatedCount;

            return Inertia::render('Admin/Language/Translations', [
                'language' => $language,
                'translations' => $translations,
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'component' => $component,
                    'per_page' => $perPage,
                ],
                'stats' => [
                    'total' => $totalTranslations,
                    'translated' => $translatedCount,
                    'missing' => $missingCount,
                ],
                'components' => $this->getAvailableComponents(),
            ]);

        } catch (\Exception $e) {
            return Inertia::render('Admin/Language/Translations', [
                'language' => $language,
                'translations' => [],
                'filters' => [
                    'search' => '',
                    'status' => 'all',
                    'component' => 'general',
                    'per_page' => 20,
                ],
                'stats' => [
                    'total' => 0,
                    'translated' => 0,
                    'missing' => 0,
                ],
                'components' => $this->getAvailableComponents(),
                'error' => 'Unable to load translations. Please try again.'
            ]);
        }
    }

    public function updateTranslation(Request $request, Language $language, Translation $translation): RedirectResponse
    {
        $request->validate([
            'value' => 'required|string',
        ]);

        try {
            $translation->update([
                'value' => $request->value,
            ]);

            Cache::forget("translations.{$language->code}");

            return redirect()->back()->with('success', 'Translation updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to update translation. Please try again.']);
        }
    }

    public function storeTranslation(Request $request, Language $language): RedirectResponse
    {
        $request->validate([
            'key' => 'required|string|unique:translations,key,NULL,id,language_id,' . $language->id,
            'value' => 'nullable|string',
        ]);

        try {
            Cache::forget('frontend_languages');
            Translation::create([
                'language_id' => $language->id,
                'key' => $request->key,
                'value' => $request->value ?? '',
                'is_dynamic' => true,
            ]);

            return redirect()->back()->with('success', 'Translation key added successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to add translation key. Please try again.']);
        }
    }

    public function deleteTranslation(Language $language, $translationId): RedirectResponse
    {
        try {
            Cache::forget('frontend_languages');
            $translation = Translation::where('language_id', $language->id)
                ->where('id', $translationId)
                ->firstOrFail();

            $translation->delete();

            return redirect()->back()->with('success', 'Translation key deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to delete translation key. Please try again.']);
        }
    }

    public function setDefault(Language $language): RedirectResponse
    {
        try {
            Cache::forget('frontend_languages');
            Language::where('is_default', true)->update(['is_default' => false]);
            $language->update(['is_default' => true]);

            return redirect()->back()->with('success', 'Default language set successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to set default language. Please try again.']);
        }
    }

    private function createBaseTranslations(Language $language): void
    {
        $allKeys = [];
        foreach (array_keys($this->getAvailableComponentsMap()) as $component) {
            if ($component !== 'dynamic') {
                $allKeys = array_merge($allKeys, $this->getComponentKeys($component));
            }
        }

        $uniqueKeys = array_unique($allKeys);

        foreach ($uniqueKeys as $key) {
            Translation::create([
                'language_id' => $language->id,
                'key' => $key,
                'value' => '',
                'is_dynamic' => 0,
            ]);
        }
    }

    private function getAvailableComponents(): array
    {
        return [
            ['value' => 'general', 'label' => 'General & Common'],
            ['value' => 'hero', 'label' => 'Hero & Landing'],
            ['value' => 'dashboard', 'label' => 'Dashboard'],
            ['value' => 'navigation', 'label' => 'Navigation'],
            ['value' => 'transactions', 'label' => 'Transactions'],
            ['value' => 'earnings', 'label' => 'Earnings'],
            ['value' => 'wallet', 'label' => 'Wallet'],
            ['value' => 'deposits', 'label' => 'Deposits & Withdrawals'],
            ['value' => 'investment', 'label' => 'Investment & Staking'],
            ['value' => 'referrals', 'label' => 'Referrals & Network'],
            ['value' => 'ai_recommendations', 'label' => 'AI Recommendations'],
            ['value' => 'support', 'label' => 'Support & Tickets'],
            ['value' => 'authentication', 'label' => 'Authentication'],
            ['value' => 'profile', 'label' => 'Profile & Settings'],
            ['value' => 'kyc', 'label' => 'KYC Verification'],
            ['value' => 'wallet_modal', 'label' => 'Wallet Modal'],
            ['value' => 'wallet_connection', 'label' => 'Wallet Connection'],
            ['value' => 'legal', 'label' => 'Legal Pages'],
            ['value' => 'footer', 'label' => 'Footer'],
            ['value' => 'dynamic', 'label' => 'Dynamic Content'],
        ];
    }

    private function getAvailableComponentsMap(): array
    {
        return [
            'general' => 'General & Common',
            'hero' => 'Hero & Landing',
            'dashboard' => 'Dashboard',
            'navigation' => 'Navigation',
            'transactions' => 'Transactions',
            'earnings' => 'Earnings',
            'wallet' => 'Wallet',
            'deposits' => 'Deposits & Withdrawals',
            'investment' => 'Investment & Staking',
            'referrals' => 'Referrals & Network',
            'ai_recommendations' => 'AI Recommendations',
            'support' => 'Support & Tickets',
            'authentication' => 'Authentication',
            'profile' => 'Profile & Settings',
            'kyc' => 'KYC Verification',
            'wallet_modal' => 'Wallet Modal',
            'wallet_connection' => 'Wallet Connection',
            'legal' => 'Legal Pages',
            'footer' => 'Footer',
            'dynamic' => 'Dynamic Content',
        ];
    }

    private function getComponentKeys(string $component): array
    {
        $components = [
            'general' => [
                'trustedBy', 'organizations', 'search', 'filters', 'clear', 'refresh', 'details', 'action',
                'previous', 'next', 'page', 'currentPage', 'goToPage', 'pagination', 'goToPreviousPage',
                'goToNextPage', 'currentPageNumber', 'goToPageNumber', 'viewAll', 'goToDashboard',
                'closeModal', 'total', 'goal', 'level', 'copy', 'copied', 'of', 'to', 'showing', 'results',
                'showingResults', 'view', 'viewDetails', 'close', 'cancel', 'cancelling', 'create', 'id',
                'all', 'pending', 'cancelled', 'completed', 'processing', 'failed', 'success', 'created',
                'approved', 'rejected', 'notAvailable', 'invalidDate', 'invalidTime', 'unknown',
                'copiedToClipboard', 'show', 'data', 'filterBy', 'records', 'recordsFound', 'from', 'with',
                'about', 'on', 'for', 'type', 'date', 'amount', 'status', 'actions', 'description',
                'category', 'priority', 'timeline', 'progress', 'duration', 'active', 'inactive',
                'enabled', 'disabled', 'yes', 'no', 'optional', 'required', 'loading', 'error', 'warning',
                'info', 'confirm', 'submit', 'save', 'delete', 'edit', 'update', 'add', 'remove', 'select',
                'selectOption', 'notApplicable'
            ],

            'hero' => [
                'heroProfessional', 'heroWeb3Finance', 'heroBeyondBoundaries', 'heroTagline',
                'heroConnectWallet', 'heroWalletConnected', 'heroCopyAddress', 'heroAddressCopied',
                'heroDisconnect', 'heroBalance', 'supportedWeb3Wallets', 'featureAssetSwappingTitle',
                'featureAssetSwappingDesc', 'featureSharedOwnershipTitle', 'featureSharedOwnershipDesc',
                'featureCarbonIntegrityTitle', 'featureCarbonIntegrityDesc', 'stakingRewards',
                'accessProfessionalDefi', 'liquidStaking', 'liveOpportunities', 'realEstate',
                'yieldFarming', 'platformStats', 'totalValueLocked', 'activeWallets', 'adminPanel', 'welcomeUser',
                'administrator',
            ],

            'dashboard' => [
                'dashboard', 'overview', 'portfolioValue', 'investmentDistribution',
                'assetAllocation', 'distributionPieChart', 'doughnutChart', 'noDistributionData',
                'distributionLegend', 'colorIndicator', 'investmentsActions', 'activeInvestmentPlans',
                'monitorInvestments', 'activeInvestmentsList', 'investmentPlan', 'investmentAmount',
                'dashboardRefreshed', 'failedToRefresh', 'investmentDashboard', 'portfolioStatistics',
                'portfolioBalance', 'dailyChange', 'plus', 'minus', 'percent', 'last24h', 'totalInvested',
                'activePlans', 'roi', 'referralIncome', 'portfolioOverview', 'realTimeAssetManagement',
                'totalPortfolioValue', 'dailyYield24h', 'growthTrend', 'portfolioActive', 'updated',
                'secondsAgo', 'chartsAnalytics', 'portfolioPerformance', 'realTimeTracking',
                'chartTimeframe', 'portfolioChart', 'lineChart', 'dailyRate', 'daily', 'weekly',
                'monthly', 'complete', 'noActiveInvestments', 'quickActions', 'quickActionLinks',
                'navigateDeposit', 'navigateInvest', 'navigateWithdraw', 'aiInsights',
                'aiRecommendationsList', 'aiRecommendation', 'confidence', 'confidenceScore',
                'learnMore', 'transactionsReferrals', 'transactionsList'
            ],

            'navigation' => [
                'platformStatusIndicator', 'livePlatform', 'activeUsers', 'mainNavigation',
                'accountInformationControls', 'dailyPerformanceChange',
                'userMenu', 'toggleMobileNavigation', 'mobileNavigation', 'uid', 'change', 'profile',
                'logout', 'settings'
            ],

            'transactions' => [
                'withdrawal', 'credit', 'debit', 'transfer', 'payment', 'refund', 'noTransactionsYet',
                'transactions', 'transactionStatistics', 'completedTransactions', 'transactionIdPlaceholder',
                'allTypes', 'startDate', 'endDate', 'applyFilters', 'refreshingTransactions',
                'refreshTransactions', 'transactionHistory', 'totalTransactionsCount',
                'transactionHistoryTable', 'walletType', 'postBalance', 'viewDetailsFor',
                'noTransactionsFound', 'transactionDetails', 'transactionHistoryRefreshed',
                'failedToRefreshHistory', 'transactionId', 'trxId', 'transaction',
                'transactionInformation', 'recentTransactions', 'latestActivity', 'totalTransactions',
                'amountDetails'
            ],

            'earnings' => [
                'earnings', 'financials', 'earningsDashboard', 'earningsStatistics', 'investmentProfit',
                'stakes', 'earningsSummary', 'monthlyBreakdown', 'investmentProfits', 'referralCommissions',
                'referralCommission', 'quickStats', 'avgDaily', 'thisWeek', 'thisMonth',
                'transactionSummary', 'totalCredits', 'totalDebits', 'totalFees', 'netEarnings',
                'transactionLogs', 'completeTransactionHistory', 'filterTransactions',
                'transactionLogsTable', 'stakingReward', 'viewingTransactionDetails', 'totalEarnings',
                'totalEarned', 'recentEarnings', 'last30Days'
            ],

            'wallet' => [
                'fundManagement', 'wallet', 'securelyManageFunds', 'depositFunds', 'withdrawFunds',
                'deposit', 'withdraw', 'deposits', 'withdrawals', 'walletBalance', 'availableBalance',
                'available', 'balance', 'canWithdraw', 'totalDeposits', 'totalWithdrawals',
                'successfulDeposits', 'successfulWithdrawals', 'pendingDeposits', 'pendingWithdrawals',
                'allTime', 'setting', 'walletDetails', 'completeWalletInformation', 'lastActivity',
                'bonusBalance', 'totalReferralBonus', 'totalWithdrawn', 'totalWithdraw', 'walletStatus',
                'locked', 'walletAddressCopied', 'currentBalance'
            ],

            'deposits' => [
                'selectPaymentMethod', 'selectWithdrawalMethod', 'paymentInstructions',
                'withdrawalInstructions', 'depositAmount', 'withdrawalAmount', 'amountPlaceholder',
                'min', 'max', 'minMax', 'fee', 'processingFee', 'charge', 'requestedAmount',
                'totalPayable', 'totalAmount', 'youWillReceive', 'youReceive', 'youReceived',
                'addedToWallet', 'deductedFromWallet', 'submitDepositRequest', 'submitWithdrawalRequest',
                'submitRequest', 'paymentSummary', 'withdrawalSummary', 'gatewayDetails',
                'gatewayInformation', 'gateway', 'paymentGateway', 'withdrawalGateway', 'currency',
                'instantManual', 'quickTips', 'doubleCheckDetails', 'verifyPaymentInfo',
                'keepProofOfPayment', 'saveTransactionReceipt', 'processingTime', 'depositsProcessed24h',
                'verifyAccountInfo', 'doubleCheckWithdrawalDetails', 'processingTimeVaries',
                'withdrawalsProcessed24h', 'feesApply', 'checkFeeBeforeWithdrawal', 'depositHistory',
                'withdrawalHistory', 'trackYourDeposits', 'trackYourWithdrawals', 'viewAllHistory',
                'viewAllPlans', 'depositDetails', 'withdrawalDetails', 'adminResponse', 'noDepositsYet',
                'noWithdrawalsYet', 'makeYourFirstDeposit', 'makeYourFirstWithdrawal', 'noDepositsFound',
                'noWithdrawalsFound', 'cancelWithdrawal', 'cancelWithdrawalRequest',
                'cancelWithdrawalConfirmation', 'keepWithdrawal', 'yesCancelWithdrawal', 'depositStats',
                'withdrawalStats', 'totalDepositsCount', 'totalWithdrawalsCount', 'successful',
                'makeDeposit', 'addFunds', 'newInvestment', 'browsePlans', 'requestPayout',
                'depositRequestSubmitted', 'withdrawalRequestSubmitted', 'depositSubmissionFailed',
                'withdrawalSubmissionFailed', 'gatewaySelected', 'invalidPaymentGateway',
                'invalidWithdrawalGateway', 'fileSizeTooLarge', 'invalidFileType',
                'fileSelectedSuccessfully', 'ensureRequiredFieldsFilled', 'invalidDepositSelected',
                'invalidWithdrawalSelected', 'withdrawalCancelledSuccessfully', 'failedToCancelWithdrawal',
                'failedToLoadPage', 'tip1Title', 'tip1Description', 'tip2Title', 'tip2Description',
                'tip3Title', 'tip3Description', 'requested', 'received', 'depositStatistics',
                'chooseGateway', 'verifyPaymentDetails', 'doubleCheckDepositInfo', 'instantProcessing',
                'depositsProcessedQuickly', 'secureTransactions', 'allDepositsEncrypted', 'totalPaid',
                'toWallet', 'additionalDetails', 'selectCryptocurrency', 'cryptoPaymentInfo',
                'cryptoPaymentDescription', 'paymentDetails', 'paymentInformation', 'paymentCredentials',
                'withdrawalInformation', 'willBeRefunded'
            ],

            'investment' => [
                'Invest', 'investmentStaking', 'investmentStakingDashboard', 'investmentPlansSubtitle',
                'stakingPoolsSubtitle', 'investmentSuccessful', 'investmentFailed', 'stakingSuccessful',
                'stakingFailed', 'viewingDetails', 'viewingHistory', 'unstakingProcessing',
                'claimingRewards', 'totalReturns', 'bestAPY', 'totalPlans', 'calculateReturns',
                'openCalculator', 'enterAmount', 'investors', 'investmentLogs', 'trackInvestments',
                'monitorStaking', 'viewHistory', 'noInvestmentsFound', 'startInvesting', 'stakingPools',
                'stakedAmount', 'rewards', 'claimRewards', 'unstake', 'noStakesFound', 'startStaking',
                'investmentPlans', 'choosePerfectPlan', 'apy', 'investmentPeriod', 'days', 'compounding',
                'investNow', 'confirmInvestment', 'expectedReturn', 'maturityDate', 'investmentCalculator',
                'totalReturn', 'lockPeriod', 'totalStaked', 'stakeNow', 'confirmStaking', 'unlockDate',
                'pendingRewards', 'nextReturn', 'maturity', 'noInvestmentLogs', 'noStakingLogs',
                'lockPeriodDays', 'lowRisk', 'mediumRisk', 'highRisk', 'investmentSummary', 'dailyReturn',
                'totalExpected', 'estAnnualRewards', 'trackInvestmentActivities', 'filterInvestmentLogs',
                'investmentLogsList', 'investmentIn', 'invested', 'expected', 'earned', 'returns',
                'returnsPaid', 'investmentProgress', 'percentComplete', 'started',
                'noInvestmentLogsFound', 'startInvestingToSeeActivities', 'investment', 'stakingHub',
                'growWealthDescription', 'platformFeatures', 'activeInvestors', 'support247', 'support',
                'aiPowered', 'staking', 'fromStaking', 'new', 'up', 'investIn', 'interestRate',
                'processingInvestment', 'invalidAmountRange', 'openInvestmentCalculator',
                'calculatePotentialReturns', 'featured', 'comingSoon', 'range', 'principal', 'capacity',
                'planComingSoon', 'closeCalculator', 'calculateYourPotentialReturns', 'minimum',
                'maximum', 'dailyProfit', 'selectThisPlan', 'stakingLogs', 'monitorStakingPositions',
                'filterStakingLogs', 'stakingLogsList', 'stakingIn', 'autoCompound', 'autoCompoundEnabled',
                'current', 'unlocked', 'unlocks', 'earningDaily', 'expectedAnnualReturn',
                'noStakingLogsFound', 'startStakingToSeeActivities', 'earnPassiveIncome', 'highYield',
                'apyRate', 'low', 'medium', 'high', 'risk', 'flexible', 'minStake', 'poolUsed',
                'spaceLeft', 'poolCapacity', 'stakeIn', 'selectInvestmentType', 'switchToInvestmentPlans',
                'switchToStakingPools', 'stake', 'stakeAmount', 'enableAutoCompound',
                'autoCompoundDescription', 'stakingSummary', 'estimatedAnnualRewards', 'processingStaking',
                'amountBelowMinimum', 'investmentHistory', 'totalInvestments', 'plan', 'investmentDetails',
                'investmentInformation', 'investmentId', 'investedAmount', 'earnedToDate', 'totalPotential',
                'completion', 'returnsRemaining', 'stakingHistory', 'totalStakes', 'staked', 'pool',
                'stakeDetails', 'stakeInformation', 'stakeId', 'totalRewards', 'totalValue', 'lockProgress',
                'lastClaim', 'riskLevel', 'minInvestment', 'profit'
            ],

            'referrals' => [
                'referralNetwork', 'networkPerformance', 'manageNetwork', 'totalReferralsStats',
                'totalReferrals', 'activeMembersStats', 'activeMembers', 'commissionEarned',
                'commissionProgress', 'yourReferralCode', 'copyReferralCode', 'referralCodeCopied',
                'failedToCopy', 'referral', 'network', 'referralDashboard', 'referralStatistics',
                'growth', 'pendingCommission', 'awaiting', 'teamInvestment', 'members',
                'referralMainContent', 'yourReferralLink', 'shareThisLink', 'linkCopied',
                'copyReferralLink', 'failedToCopyLink', 'commissionStructure', 'earnUpToLevels',
                'commissionTierLevels', 'commission', 'both', 'depositCommission', 'reinvestment',
                'networkOverview', 'activeReferrals', 'referralCode', 'recentActivityTopReferrers',
                'recentCommissions', 'latestReferralEarnings', 'viewAllCommissions',
                'recentCommissionsList', 'topReferrals', 'yourHighestEarners', 'viewAllReferrals',
                'topPerformingReferrals', 'paid', 'referrals', 'referralLinkCopied', 'totalCommissions',
                'checkYourLevel', 'paidCommissions', 'approvedCommissions', 'pendingCommissions',
                'commissionHistory', 'allReferralCommissions', 'referredUser', 'baseAmount', 'rate',
                'noCommissionsFound', 'noCommissionHistory', 'referralList', 'fromReferrals',
                'totalVolume', 'myReferrals', 'completeReferralNetwork', 'member', 'generations',
                'joinedDate', 'noReferralsFound', 'invitePeopleToEarn', 'totalNetwork', 'allLevels',
                'currentlyActive', 'fromNetwork', 'networkDepth', 'networkTree', 'visualizeYourNetwork',
                'expandAll', 'collapseAll', 'noNetworkData', 'startBuildingNetwork', 'levelBreakdown',
                'earningsByLevel', 'noLevelData', 'noEarningsData', 'viewNetwork', 'networkDashboard',
                'networkStatistics', 'networkMainContent', 'maxLevels', 'networkHierarchy',
                'clickToExpand', 'allNodesExpanded', 'allNodesCollapsed', 'noReferralsYet',
                'viewReferralCode', 'referralBonuses', 'achieveTheseBonus', 'milestoneBonuses',
                'achievementBonuses', 'loyaltyBonuses', 'claimed', 'eligible', 'reward', 'recurring',
                'every', 'requires', 'expandOrCollapse', 'howItWorks', 'shareReferralCode',
                'whenTheySignUp', 'earnCommissions', 'buildDeeperNetwork', 'networkLevels',
                'level1Direct', 'level2', 'level3', 'level4Plus', 'referralLevel',
                'referralLevelDashboard', 'currentLevel', 'yourActiveCommissionRate', 'yourReferrals',
                'levelProgress', 'previousLevel', 'completedMilestone', 'requiredReferrals',
                'requiredInvestment', 'nextLevel', 'unlockHigherCommission', 'inProgress',
                'targetCommission', 'referralsProgress', 'moreReferralsNeeded', 'moreInvestmentNeeded',
                'requirementsToUnlock', 'refer', 'activeMembersAnd', 'achieve', 'inTeamInvestment',
                'maxLevel', 'maximumLevelReached', 'highestCommissionRate', 'viewLevel', 'referralBonus',
                'referredBy'
            ],

            'ai_recommendations' => [
                'aiRecommendations', 'smartInsights', 'personalizedInvestmentInsights',
                'yourRecommendations', 'aiPoweredInvestmentSuggestions', 'totalRecommendations',
                'unread', 'applied', 'live', 'activeOnly', 'noRecommendations',
                'clickGenerateToGetPersonalizedInsights', 'dismiss', 'generating', 'generateNew',
                'recommendationsGenerated', 'failedToGenerate', 'recommendationDismissed',
                'failedToDismiss', 'backToRecommendations', 'recommendationDetails', 'generatedOn',
                'veryHighConfidence', 'highConfidence', 'moderateConfidence', 'lowConfidence',
                'recommendedPlan', 'keyInsights', 'suggestedAmount', 'basedOnYourPortfolio',
                'expectedROI', 'projectedReturn', 'whyThisRecommendation', 'applyRecommendation',
                'backToList', 'howAiWorks', 'aiAnalyzesYourPortfolio', 'dataAnalysis',
                'analyzesHistoricalData', 'riskMatching', 'matchesYourRiskProfile', 'realTimeUpdates',
                'continuouslyUpdated', 'redirectingToInvestment', 'totalEarning', 'failedToApply',
                'smart', 'aiRecommendedPlan', 'withSuggestedAmount', 'viewPlan'
            ],

            'support' => [
                'supportTickets', 'helpCenter', 'supportTicketsDashboard', 'ticketStatistics',
                'totalTickets', 'openTickets', 'open', 'pendingTickets', 'waiting', 'resolvedTickets',
                'resolved', 'closed', 'ticketFilters', 'searchTickets', 'searchPlaceholder',
                'filterByStatus', 'allStatuses', 'createTicket', 'ticketsList', 'yourTickets',
                'manageSupportRequests', 'supportTicketsList', 'ticket', 'lowDescription',
                'mediumDescription', 'highDescription', 'urgent', 'urgentDescription', 'replies',
                'noSupportTicketsFound', 'createFirstTicket', 'createYourFirstTicket', 'backToTickets',
                'ticketDetails', 'attachments', 'conversation', 'supportTeam', 'addReply', 'yourMessage',
                'addFiles', 'sendReply', 'sending', 'ticketClosed', 'createSupportTicket', 'newRequest',
                'createTicketForm', 'helpInformation', 'needHelp', 'supportDescription',
                'createNewTicket', 'ticketForm', 'subject', 'subjectPlaceholder', 'selectCategory',
                'technical', 'billing', 'account', 'security', 'other', 'selectPriority',
                'descriptionPlaceholder', 'characters', 'attachmentHelp', 'uploadFiles', 'clickToUpload',
                'fileFormats', 'selectedFiles', 'removeFile', 'formActions', 'submitTicket',
                'creatingTicket', 'commonQuestions', 'faqList', 'securityQuestion', 'securityAnswer',
                'notificationQuestion', 'notificationAnswer', 'fillRequiredFields', 'ticketCreated',
                'ticketCreateFailed', 'fileTooLarge', 'replySentSuccessfully', 'replyFailed',
                'assignedTo', 'ticketNumber', 'createdAt', 'updatedAt', 'noRepliesYet', 'typeReplyHere',
                'downloading', 'download', 'file', 'size', 'filesSelected', 'fileRemoved',
                'messageRequired', 'replySubmitted', 'downloadStarted', 'downloadFailed',
                'categoryAccount', 'categoryInvestment', 'categoryWithdrawal', 'categoryDeposit',
                'categoryStaking', 'categoryReferral', 'categoryTechnical', 'categoryBilling',
                'categoryKyc', 'categorySecurity', 'categoryGeneral', 'categoryFeature',
                'priorityLowDescription', 'priorityMediumDescription', 'priorityHighDescription',
                'priorityUrgentDescription', 'filesAttached', 'subjectRequired', 'descriptionRequired',
                'categoryRequired', 'ticketCreatedSuccess', 'ticketCreatedError', 'attachFiles',
                'maxFileSize', 'attachedFiles', 'enterSubject', 'enterDescription', 'kyc', 'general',
                'feature', 'statusOpen', 'statusInProgress', 'statusResolved', 'statusClosed',
                'priorityLow', 'priorityMedium', 'priorityHigh', 'priorityUrgent', 'typeYourReplyHere',
                'attachmentsOptional', 'ticketClosedMessage'
            ],

            'authentication' => [
                'welcomeBack', 'createNewAccount', 'resetPassword', 'authenticationRequired',
                'loginToAccount', 'createYourAccount', 'enterEmailForReset', 'authSubtitle', 'email',
                'password', 'fullName', 'confirmPassword', 'newPassword', 'confirmNewPassword',
                'emailPlaceholder', 'passwordPlaceholder', 'namePlaceholder', 'confirmPasswordPlaceholder',
                'enterNewPassword', 'rememberMe', 'forgotPassword', 'signIn', 'signUp', 'createAccount',
                'sendResetLink', 'backToSignIn', 'signingIn', 'creatingAccount', 'sendingResetLink',
                'resettingPassword', 'securityNotice', 'passwordResetSuccess', 'resetPasswordFor',
                'resettingPasswordFor', 'passwordRequirements'
            ],

            'profile' => [
                'areYouSureRemoveAvatar', 'removing', 'removePhoto', 'reviewing', 'selectValidImageFile',
                'imageSizeTooLarge', 'drivingLicense', 'avatarUpdatedSuccess', 'avatarUpdateFailed',
                'avatarRemovedSuccess', 'avatarRemoveFailed', 'profileUpdatedSuccess', 'profileUpdateFailed',
                'currentPasswordRequired', 'newPasswordRequired', 'passwordMinLength', 'passwordMismatch',
                'passwordUpdatedSuccess', 'passwordUpdateFailed', 'updatePassword', 'currentPassword',
                'enterCurrentPassword', 'confirmYourNewPassword', 'kycSubmittedSuccess', 'kycSubmitFailed',
                'firstName', 'lastName', 'dateOfBirth', 'phoneNumber', 'address', 'city', 'state',
                'country', 'postalCode', 'documentInformation', 'uploadDocuments', 'accountInformation',
                'profileInformation', 'profilePhoto', 'updateProfile', 'updating', 'userId', 'memberSince'
            ],

            'kyc' => [
                'kycVerification', 'verifyYourIdentity', 'kycApproved', 'kycUnderReview', 'submitKyc',
                'submitting', 'documentType', 'selectDocumentType', 'documentNumber', 'documentFrontImage',
                'documentBackImage', 'selfieWithDocument', 'clickToUploadFront', 'clickToUploadBack',
                'clickToUploadSelfie', 'jpgPngMax2mb', 'passport', 'nationalId', 'driversLicense',
                'gender', 'selectGender', 'male', 'female'
            ],

            'wallet_modal' => [
                'walletModalEmailLogin', 'walletModalConnectionFailed', 'walletModalTryAgain',
                'walletModalPasswordResetSent', 'walletModalBackToLogin', 'walletModalForgotPasswordTitle',
                'walletModalManualLoginTitle', 'walletModalConnectWalletTitle',
                'walletModalForgotPasswordDesc', 'walletModalManualLoginDesc', 'walletModalConnectWalletDesc',
                'walletModalEmailLabel', 'walletModalEmailPlaceholder', 'walletModalResetPassword',
                'walletModalBackToLoginLink', 'walletModalPasswordLabel', 'walletModalPasswordPlaceholder',
                'walletModalForgotPasswordLink', 'walletModalContinue', 'walletModalBackToOptions',
                'walletModalAutoAccountCreate', 'walletModalEmailTitle', 'walletModalEmailDesc',
                'walletModalTerms', 'walletModalMetaMaskDesc', 'walletModalConnecting',
                'walletModalConfirmWallet', 'walletModalLoggingIn', 'walletModalAuthenticating',
                'walletModalLoginFailed', 'walletModalSuccess', 'walletModalRedirecting',
                'walletModalResetFailed', 'walletModalResetSuccess', 'walletModalNotAvailable',
                'walletModalNotInstalled', 'walletModalRequestingAccess', 'walletModalApproveConnection',
                'walletModalNoAccounts', 'walletModalAuthenticatingWallet', 'walletModalConnectionRejected',
                'walletModalConnectionTimeout', 'walletModalConnectionError', 'walletModalGettingNonce',
                'walletModalSigningMessage', 'walletModalConfirmInWallet', 'walletModalNonceFailed',
                'walletModalSignatureFailed', 'walletModalSignatureRejected', 'walletModalPleaseWait',
                'walletModalLoginTab', 'walletModalRegisterTab', 'walletModalLoginButton',
                'walletModalRegisterButton', 'walletModalNameLabel', 'walletModalNamePlaceholder',
                'walletModalConfirmPasswordLabel', 'walletModalConfirmPasswordPlaceholder',
                'walletModalCreatingAccount', 'walletModalFillAllFields', 'walletModalPasswordMismatch',
                'walletModalPasswordTooShort', 'walletModalRegistrationFailed', 'walletModalBackToWallets',
                'walletModalCheckEmail'
            ],

            'wallet_connection' => [
                'connectWalletUpper', 'connectWeb3Wallet', 'connectYourWallet', 'connectingToWallet',
                'connectingUpper', 'connectionFailed', 'connectionRejected', 'connectionTimeout',
                'walletAddress', 'walletAddresses', 'walletConfigNotFound', 'walletConnected',
                'walletConnection', 'walletConnectionText', 'walletProviderNotFound',
                'chooseWalletToContinue', 'noWalletDetected', 'installMetaMask', 'installTrustWallet',
                'popularEthereumWallet', 'mobileFirstWeb3Wallet', 'pleaseApproveInWallet',
                'noAccountsFound', 'failedToConnectWallet', 'authenticating', 'loggingIn', 'loginFailed',
                'redirecting', 'tryAgain', 'secureConnection', 'secureConnectionDesc'
            ],

            'legal' => [
                'termsOfService', 'lastUpdated', 'acceptanceOfTerms', 'acceptanceOfTermsText',
                'descriptionOfService', 'descriptionOfServiceText', 'userResponsibilities',
                'userResponsibilitiesText', 'provideAccurateInfo', 'maintainSecurity', 'complyWithLaws',
                'noFraudulent', 'noUnauthorizedAccess', 'financialRisks', 'financialRisksText',
                'marketVolatility', 'smartContractVulnerabilities', 'regulatoryChanges',
                'technologyFailures', 'acknowledgeRisks', 'feesAndPayments', 'feesAndPaymentsText',
                'intellectualProperty', 'intellectualPropertyText', 'limitationOfLiability',
                'limitationOfLiabilityText', 'termination', 'terminationText', 'changesToTerms',
                'changesToTermsText', 'contactInformation', 'contactInformationText', 'terms',
                'privacyPolicy', 'informationWeCollect', 'informationWeCollectText', 'emailAddress',
                'deviceInfo', 'usageData', 'howWeUseInfo', 'howWeUseInfoText', 'provideServices',
                'processTransactions', 'sendNotices', 'respondQuestions', 'protectFraud',
                'informationSharing', 'informationSharingText', 'withConsent', 'withServiceProviders',
                'complyLegal', 'protectRights', 'dataSecurity', 'dataSecurityText', 'yourRights',
                'yourRightsText', 'accessInfo', 'correctData', 'requestDeletion', 'objectProcessing',
                'dataPortability', 'cookiesTracking', 'cookiesTrackingText', 'childrensPrivacy',
                'childrensPrivacyText', 'changesToPolicy', 'changesToPolicyText', 'contactUs',
                'contactUsText', 'cookiePolicy', 'cookies', 'whatAreCookies', 'whatAreCookiesText',
                'typesOfCookies', 'essentialCookies', 'essentialCookiesText', 'analyticsCookies',
                'analyticsCookiesText', 'marketingCookies', 'marketingCookiesText', 'preferenceCookies',
                'preferenceCookiesText', 'sessionOrYear', 'upToTwoYears', 'upToOneYear',
                'thirdPartyCookies', 'thirdPartyCookiesText', 'googleAnalytics', 'googleAnalyticsDesc',
                'web3Providers', 'web3ProvidersDesc', 'managingCookies', 'managingCookiesText',
                'ourCookieSettings', 'ourCookieSettingsText', 'openCookieSettings', 'browserSettings',
                'browserSettingsText', 'impactDisablingCookies', 'impactDisablingCookiesText',
                'updatesToPolicy', 'updatesToPolicyText', 'contactUsCookies', 'contactUsCookiesText',
                'weValueYourPrivacy', 'cookieBannerDescription', 'acceptAllCookies', 'essentialOnly'
            ],

            'footer' => [
                'footerAllRightsReserved', 'allRightsReserved', 'footerPrivacyPolicy',
                'footerTermsService', 'footerCookiePolicy', 'platformNumber', 'backToHome'
            ],
        ];

        return $components[$component] ?? [];
    }
}
