import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Returns helpers to check if the current user can view a specific tab
 * within a given module, based on their tabs_json permission overrides.
 *
 * @param {string} moduleKey  - e.g. 'patrimonio', 'ocurrencias'
 * @param {string[]} allTabs  - ordered list of all tab keys in this module
 */
export function useTabPermission(moduleKey, allTabs) {
    const page = usePage();

    const canViewTab = (tabKey) => {
        const user = page.props.auth?.user;
        if (!user) return false;

        // Admins always have full access
        if (user.rol_id === 'ROL001' || user.rol_id === '1') return true;

        // No tabs_json or module not restricted → all tabs accessible
        if (!user.tabs_json || !(moduleKey in user.tabs_json)) return true;

        const allowed = user.tabs_json[moduleKey];
        if (allowed === true || allowed === null) return true;
        return Array.isArray(allowed) && allowed.includes(tabKey);
    };

    /** First tab the user is allowed to see (used as default activeTab). */
    const firstAllowedTab = computed(() => {
        for (const tab of allTabs) {
            if (canViewTab(tab)) return tab;
        }
        return allTabs[0];
    });

    return { canViewTab, firstAllowedTab };
}
