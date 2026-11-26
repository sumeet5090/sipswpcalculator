import registryData from '../../../../content/calculator_registry.json' with { type: 'json' };

export interface CalculatorModeMeta {
    is_specialized: boolean;
    is_four_card: boolean;
    enable_swp_default: boolean;
    show_lumpsum: boolean;
    form_partial: string | null;
    share_title: string;
    duration_field_id: string;
    strategy_class: string;
    form_header?: {
        theme: string;
        icon: string;
        prefix: string;
        subtitle: string;
        badge: string;
    };
}

type RegistryModesMap = Record<string, CalculatorModeMeta>;

const MODES: RegistryModesMap = registryData.modes as RegistryModesMap;

const SPECIALIZED_MODES: readonly string[] = Object.entries(MODES)
    .filter(([, meta]) => Boolean(meta.is_specialized))
    .map(([mode]) => mode);

const ALL_MODES: readonly string[] = Object.keys(MODES);

/**
 * Single Source of Truth runtime accessors synced directly from content/calculator_registry.json.
 */
export const CalculatorRegistry = {
    isSpecializedMode(mode: string | null | undefined): boolean {
        if (!mode) return false;
        return Boolean(MODES[mode]?.is_specialized);
    },

    isFourCardMode(mode: string | null | undefined): boolean {
        if (!mode) return false;
        return Boolean(MODES[mode]?.is_four_card);
    },

    getSpecializedModes(): readonly string[] {
        return SPECIALIZED_MODES;
    },

    getAllModes(): readonly string[] {
        return ALL_MODES;
    },

    getDraftClearModes(): readonly string[] {
        const set = new Set<string>([...ALL_MODES, 'target']);
        return Array.from(set);
    },

    getShareTitle(mode: string | null | undefined): string {
        if (mode && MODES[mode]?.share_title) {
            return MODES[mode].share_title;
        }
        return 'Wealth Accumulation Plan';
    },

    getDurationFieldIds(): readonly string[] {
        const ids = new Set<string>();
        Object.values(MODES).forEach((meta) => {
            if (meta.duration_field_id) {
                ids.add(meta.duration_field_id);
            }
        });
        return Array.from(ids);
    },

    getModeMeta(mode: string): CalculatorModeMeta | undefined {
        return MODES[mode];
    },
};
