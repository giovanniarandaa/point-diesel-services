import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    badge?: number;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    lowStockCount: number;
    flash: {
        success: string | null;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
}

export interface Unit {
    id: number;
    customer_id: number;
    vin: string;
    make: string;
    model: string;
    engine: string | null;
    mileage: number;
}

export interface Estimate {
    id: number;
    estimate_number: string;
    customer_id: number;
    unit_id: number | null;
    status: 'draft' | 'sent' | 'approved' | 'invoiced';
    public_token: string | null;
    subtotal_parts: string;
    subtotal_labor: string;
    shop_supplies_rate: string;
    shop_supplies_amount: string;
    tax_rate: string;
    tax_amount: string;
    total: string;
    notes: string | null;
    approved_at: string | null;
    created_at: string;
    updated_at: string;
    customer?: Customer;
    unit?: Unit;
    lines?: EstimateLine[];
    invoice?: Invoice;
}

export interface EstimateLine {
    id: number;
    estimate_id: number;
    lineable_type: string;
    lineable_id: number;
    description: string;
    quantity: number;
    unit_price: string;
    line_total: string;
    sort_order: number;
}

export interface Invoice {
    id: number;
    invoice_number: string;
    estimate_id: number;
    issued_at: string;
    subtotal_parts: string;
    subtotal_labor: string;
    shop_supplies_rate: string;
    shop_supplies_amount: string;
    tax_rate: string;
    tax_amount: string;
    total: string;
    notified_at: string | null;
    created_at: string;
    updated_at: string;
    estimate?: Estimate;
}

export interface StockWarning {
    part_id: number;
    name: string;
    sku: string;
    requested: number;
    available: number;
}

export interface CatalogItem {
    id: number;
    type: 'Part' | 'LaborService';
    name: string;
    sku: string | null;
    price: string;
}

export interface DashboardStats {
    totalEstimates: number;
    activeEstimates: number;
    invoicesThisMonth: number;
    revenueThisMonth: string;
}

export interface LowStockPart {
    id: number;
    sku: string;
    name: string;
    stock: number;
    min_stock: number;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}
