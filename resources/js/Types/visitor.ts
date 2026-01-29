export interface Visit {
    id: string | number;
    fecha: string;
    dni: string;
    nombres: string;
    hora_ingreso: string | null;
    hora_salida: string | null;
    motivo: string;
    motivo_nombre?: string;
    visit_reason_id?: string;
    area: string;
    a_quien_visita: string | null;
    is_pending: boolean;
}

export interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedVisits {
    data: Visit[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLinks[];
}
