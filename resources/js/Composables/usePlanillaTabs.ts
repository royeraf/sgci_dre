import type { Component } from 'vue';
import { Wallet, Receipt, Coins, Gift, Percent, PiggyBank, Clock } from 'lucide-vue-next';

export interface PlanillaTab {
    key: string;
    label: string;
    icon: Component;
    title: string;
    description: string;
}

/**
 * Fuente única de verdad de las pestañas del módulo Planillas.
 * Se usa tanto en la página (Pages/Planillas/Index.vue) como en la
 * configuración de permisos por usuario (Components/Users/UserModal.vue).
 */
export const PLANILLA_TABS: PlanillaTab[] = [
    {
        key: 'planillas',
        label: 'Planillas',
        icon: Wallet,
        title: 'Planillas',
        description: 'Generación de la planilla mensual del personal.',
    },
    {
        key: 'boletas',
        label: 'Boletas',
        icon: Receipt,
        title: 'Boletas de Pago',
        description: 'Emisión y consulta de boletas de pago por periodo.',
    },
    {
        key: 'conceptos',
        label: 'Remuneraciones',
        icon: Coins,
        title: 'Remuneraciones y Conceptos',
        description: 'Haberes, bonificaciones y asignaciones por empleado.',
    },
    {
        key: 'gratificaciones',
        label: 'Gratificaciones',
        icon: Gift,
        title: 'Gratificaciones CAS',
        description: 'Cálculo de gratificaciones de Fiestas Patrias y Navidad (Ley 32563).',
    },
    {
        key: 'descuentos',
        label: 'Retenciones / Descuentos',
        icon: Percent,
        title: 'Retenciones y Descuentos',
        description: 'Descuentos de ley, judiciales y administrativos.',
    },
    {
        key: 'aportaciones',
        label: 'Aportaciones',
        icon: PiggyBank,
        title: 'Aportaciones del Empleado',
        description: 'Aportes previsionales y de salud del personal.',
    },
    {
        key: 'tardanzas',
        label: 'Tardanzas',
        icon: Clock,
        title: 'Tardanzas',
        description: 'Cálculo de tardanzas a partir de las marcas de asistencia.',
    },
];

export const PLANILLA_TAB_KEYS: string[] = PLANILLA_TABS.map((tab) => tab.key);
