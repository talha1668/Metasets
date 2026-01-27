<script setup>
const props = defineProps({
    levelData: {
        type: Object,
        required: true
    },
    levelNumber: {
        type: Number,
        required: true
    },
    currencySymbol: {
        type: String,
        default: '$'
    },
    formatNumber: {
        type: Function,
        required: true
    },
    isLastInLevel: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['user-click']);

const getLevelColor = (level) => {
    const colors = {
        1: 'bg-blue-100 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300',
        2: 'bg-green-100 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300',
        3: 'bg-yellow-100 border-yellow-200 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300',
        4: 'bg-purple-100 border-purple-200 text-purple-800 dark:bg-purple-900/30 dark:border-purple-700 dark:text-purple-300',
        5: 'bg-pink-100 border-pink-200 text-pink-800 dark:bg-pink-900/30 dark:border-pink-700 dark:text-pink-300',
    };
    return colors[level] || 'bg-gray-100 border-gray-200 text-gray-800 dark:bg-gray-900/30 dark:border-gray-700 dark:text-gray-300';
};

const getAvatarColor = (level) => {
    const colors = {
        1: 'bg-blue-500',
        2: 'bg-green-500',
        3: 'bg-yellow-500',
        4: 'bg-purple-500',
        5: 'bg-pink-500',
    };
    return colors[level] || 'bg-gray-500';
};

const handleUserClick = (user) => {
    emit('user-click', user);
};
</script>

<template>
    <div class="relative">
        <div class="flex items-center justify-center mb-6">
            <div class="flex items-center space-x-3">
                <div class="h-px bg-gray-300 dark:bg-gray-600 flex-1 w-16"></div>
                <span
                    class="px-3 py-1 text-xs font-semibold rounded-full border"
                    :class="getLevelColor(levelNumber)"
                >
                    Level {{ levelNumber }}
                </span>
                <div class="h-px bg-gray-300 dark:bg-gray-600 flex-1 w-16"></div>
            </div>
        </div>

        <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-px h-6 bg-gray-300 dark:bg-gray-600"></div>

        <div class="flex justify-center mb-6">
            <div class="relative">
                <div
                    v-if="levelData.referrals && levelData.referrals.length > 1"
                    class="absolute top-8 left-1/2 transform -translate-x-1/2 h-px bg-gray-300 dark:bg-gray-600"
                    :style="{ width: `${(levelData.referrals.length - 1) * 200}px`, marginLeft: `-${((levelData.referrals.length - 1) * 200) / 2}px` }"
                ></div>

                <div
                    class="bg-white dark:bg-slate-700 border-2 border-gray-200 dark:border-slate-600 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer min-w-[180px] relative z-10"
                    @click="handleUserClick(levelData.user)"
                >
                    <div class="text-center">
                        <div
                            class="inline-flex items-center justify-center w-12 h-12 rounded-full text-white font-bold text-sm mb-2"
                            :class="getAvatarColor(levelNumber)"
                        >
                            {{ levelData.user.username?.charAt(0).toUpperCase() || 'U' }}
                        </div>

                        <div class="space-y-1">
                            <div class="font-medium text-gray-900 dark:text-white text-sm">
                                {{ levelData.user.username }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ levelData.user.email }}
                            </div>

                            <div class="mt-2 space-y-1">
                                <div class="text-xs text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Invested:</span>
                                    {{ currencySymbol }}{{ formatNumber(levelData.user.total_invested || 0) }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Earned:</span>
                                    {{ currencySymbol }}{{ formatNumber(levelData.user.total_referral_bonus || 0) }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Referrals:</span>
                                    {{ levelData.referrals?.length || 0 }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Joined: {{ new Date(levelData.user.created_at).toLocaleDateString() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="levelData.referrals && levelData.referrals.length > 0" class="relative">
            <div
                v-for="(child, index) in levelData.referrals"
                :key="child.user.id"
                class="absolute top-0 w-px h-6 bg-gray-300 dark:bg-gray-600"
                :style="{
                    left: `${50 + ((index - (levelData.referrals.length - 1) / 2) * 200 / levelData.referrals.length * levelData.referrals.length)}%`,
                    transform: 'translateX(-50%)'
                }"
            ></div>

            <div class="pt-6">
                <div
                    class="grid gap-8"
                    :class="{
                        'grid-cols-1': levelData.referrals.length === 1,
                        'grid-cols-2': levelData.referrals.length === 2,
                        'grid-cols-3': levelData.referrals.length === 3,
                        'grid-cols-4': levelData.referrals.length >= 4
                    }"
                >
                    <div v-for="(child, index) in levelData.referrals" :key="child.user.id" class="flex justify-center">
                        <NetworkLevel
                            :level-data="child"
                            :level-number="levelNumber + 1"
                            :currency-symbol="currencySymbol"
                            :format-number="formatNumber"
                            :is-last-in-level="index === levelData.referrals.length - 1"
                            @user-click="handleUserClick"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="levelNumber < 5" class="flex justify-center mt-4">
            <div class="text-xs text-gray-400 dark:text-gray-500 italic">
                No referrals
            </div>
        </div>
    </div>
</template>

<style scoped>
.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

@media (max-width: 768px) {
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .grid-cols-2,
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}
</style>
