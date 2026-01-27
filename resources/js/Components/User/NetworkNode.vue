<script setup>

import {useTranslation} from "@/composables/useTranslation.js";

const { t } = useTranslation();

const props = defineProps({
    node: { type: Object, required: true },
    level: { type: Number, required: true },
    isExpanded: { type: Boolean, default: false },
    themeColors: { type: Object, required: true },
    currencySymbol: { type: String, default: '$' },
    expandedNodes: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['toggle']);

const toggleNode = () => emit('toggle', props.node.id);
const handleChildToggle = (childId) => emit('toggle', childId);

const getInitials = (name) => {
    if (!name) return 'NA';
    const parts = name.split(' ');
    return parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : name.substring(0, 2).toUpperCase();
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(parseFloat(amount) || 0);
};

const formatStatus = (status) => {
    if (!status) return t('unknown') || 'Unknown';
    return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
};

const getStatusStyle = (status) => {
    if (status === 'active') {
        return {
            backgroundColor: `rgba(${props.themeColors.primaryRgb}, 0.2)`,
            color: props.themeColors.primary
        };
    }
    return {
        backgroundColor: 'rgba(148, 163, 184, 0.2)',
        color: '#94a3b8'
    };
};
</script>

<template>
    <div class="space-y-2.5">
        <div class="network-node">
            <div
                class="flex items-center justify-between p-3.5 sm:p-4 bg-gradient-to-br from-slate-900/80 to-slate-800/60 backdrop-blur-xl border border-slate-700/40 rounded-lg hover:border-slate-600/50 hover:shadow-lg hover:shadow-slate-900/20 transition-all duration-300 cursor-pointer group"
                :style="`margin-left: ${(level - 1) * 20}px;`"
                @click="toggleNode"
                role="button"
                tabindex="0"
                :aria-label="`${t('expandOrCollapse')} ${node.name}`"
                @keypress.enter="toggleNode"
                @keypress.space.prevent="toggleNode">
                <div class="flex items-center gap-2.5 sm:gap-3 flex-1 min-w-0">
                    <div
                        v-if="node.children && node.children.length > 0"
                        class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center transition-all duration-300"
                        :style="`background-color: rgba(${themeColors.primaryRgb}, 0.2);`"
                        :class="{ 'rotate-90': isExpanded }">
                        <svg class="w-3.5 h-3.5" :style="{ color: themeColors.primary }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                    <div v-else class="flex-shrink-0 w-6 h-6"></div>

                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover:scale-110"
                         :style="`background: linear-gradient(135deg, rgba(${themeColors.primaryRgb}, 0.2), rgba(${themeColors.primaryRgb}, 0.1)); border: 2px solid rgba(${themeColors.primaryRgb}, 0.3);`">
                        <span class="text-xs font-bold" :style="{ color: themeColors.primary }">
                            {{ getInitials(node.name) }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white text-sm truncate">{{ node.name }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-xs font-medium text-slate-400 truncate max-w-[200px]">{{ node.email }}</span>
                            <span class="px-1.5 py-0.5 text-xs font-semibold rounded-full flex-shrink-0"
                                  :style="getStatusStyle(node.status)">
                                {{ formatStatus(node.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 sm:gap-4 flex-shrink-0">
                    <div class="hidden sm:block text-center">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5">{{ t('level') }}</p>
                        <p class="text-sm font-bold text-white">{{ node.level }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5">{{ t('earned') }}</p>
                        <p class="text-sm font-bold" :style="{ color: themeColors.primary }">
                            {{ currencySymbol }}{{ formatCurrency(node.total_earned) }}
                        </p>
                    </div>
                    <div class="hidden md:block text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5">{{ t('investment') }}</p>
                        <p class="text-sm font-semibold text-white">
                            {{ currencySymbol }}{{ formatCurrency(node.total_investment) }}
                        </p>
                    </div>
                    <div v-if="node.children && node.children.length > 0"
                         class="flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-300 group-hover:scale-110"
                         :style="`background-color: rgba(${themeColors.primaryRgb}, 0.15);`">
                        <span class="text-xs font-bold" :style="{ color: themeColors.primary }">
                            {{ node.generation_count }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="isExpanded && node.children && node.children.length > 0" class="mt-2.5 space-y-2.5 animate-fade-in">
                <NetworkNode
                    v-for="child in node.children"
                    :key="child.id"
                    :node="child"
                    :level="level + 1"
                    :is-expanded="expandedNodes[child.id] || false"
                    :theme-colors="themeColors"
                    :currency-symbol="currencySymbol"
                    :expanded-nodes="expandedNodes"
                    @toggle="handleChildToggle"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.rotate-90 {
    transform: rotate(90deg);
}

.network-node {
    animation: fadeIn 0.3s ease-in-out;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.backdrop-blur-xl {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

[role="button"]:focus {
    outline: 2px solid v-bind('`rgba(${themeColors.primaryRgb}, 0.4)`');
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
    .network-node,
    .animate-fade-in,
    .transition-all {
        animation: none !important;
        transition: none !important;
    }
}

@media (max-width: 640px) {
    .flex {
        gap: 0.5rem;
    }
}

*:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.group:hover {
    will-change: transform, box-shadow;
}
</style>
