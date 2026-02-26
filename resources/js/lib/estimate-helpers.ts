import { type Estimate } from '@/types';

export const STATUS_VARIANTS: Record<Estimate['status'], 'secondary' | 'default' | 'destructive' | 'outline'> = {
    draft: 'secondary',
    sent: 'default',
    approved: 'outline',
    invoiced: 'outline',
};

export const STATUS_LABELS: Record<Estimate['status'], string> = {
    draft: 'Draft',
    sent: 'Sent',
    approved: 'Approved',
    invoiced: 'Invoiced',
};

export function formatCurrency(value: string | number): string {
    return `$${Number(value).toFixed(2)}`;
}

export function formatLineType(lineableType: string): string {
    return lineableType.includes('Part') ? 'Part' : 'Service';
}
