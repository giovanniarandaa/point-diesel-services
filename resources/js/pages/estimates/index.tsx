import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Estimate, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Edit, Plus, Search, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface Props {
    estimates: PaginatedData<Estimate>;
    filters: {
        search: string | null;
        status: string | null;
    };
}

const statusVariants: Record<string, 'secondary' | 'default' | 'destructive' | 'outline'> = {
    draft: 'secondary',
    sent: 'default',
    approved: 'outline',
    invoiced: 'outline',
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    sent: 'Sent',
    approved: 'Approved',
    invoiced: 'Invoiced',
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Estimates', href: '/estimates' }];

export default function EstimatesIndex({ estimates, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const performSearch = useCallback(
        (value: string) => {
            router.get(
                route('estimates.index'),
                { search: value || undefined, status: filters.status || undefined },
                { preserveState: true, replace: true },
            );
        },
        [filters.status],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            performSearch(search);
        }, 300);

        return () => clearTimeout(timer);
    }, [search, performSearch]);

    const toggleStatusFilter = (status: string) => {
        const newStatus = filters.status === status ? undefined : status;
        router.get(route('estimates.index'), { search: filters.search || undefined, status: newStatus }, { preserveState: true, replace: true });
    };

    const handleDelete = (estimate: Estimate) => {
        if (confirm(`Are you sure you want to delete estimate "${estimate.estimate_number}"? This action cannot be undone.`)) {
            router.delete(route('estimates.destroy', estimate.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Estimates" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">Estimates</h2>
                        <p className="text-muted-foreground text-sm">Create and manage customer estimates</p>
                    </div>
                    <Button asChild>
                        <Link href={route('estimates.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Estimate
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative max-w-sm">
                        <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by estimate # or customer..."
                            className="pl-10"
                        />
                    </div>
                    <div className="flex gap-1">
                        {Object.entries(statusLabels).map(([value, label]) => (
                            <Button
                                key={value}
                                variant={filters.status === value ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => toggleStatusFilter(value)}
                            >
                                {label}
                            </Button>
                        ))}
                    </div>
                </div>

                {estimates.data.length === 0 ? (
                    <div className="flex min-h-[400px] items-center justify-center rounded-lg border border-dashed">
                        <p className="text-muted-foreground text-sm">
                            {filters.search || filters.status
                                ? 'No estimates found matching your criteria.'
                                : 'No estimates yet. Create your first estimate to get started.'}
                        </p>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Estimate #</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Total</TableHead>
                                    <TableHead className="text-right">Date</TableHead>
                                    <TableHead className="w-[100px]" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {estimates.data.map((estimate) => (
                                    <TableRow key={estimate.id}>
                                        <TableCell>
                                            <Link href={route('estimates.show', estimate.id)} className="font-mono font-medium hover:underline">
                                                {estimate.estimate_number}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{estimate.customer?.name}</TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {estimate.unit ? `${estimate.unit.make} ${estimate.unit.model}` : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={statusVariants[estimate.status] ?? 'secondary'}>
                                                {statusLabels[estimate.status] ?? estimate.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right font-medium">${Number(estimate.total).toFixed(2)}</TableCell>
                                        <TableCell className="text-muted-foreground text-right text-sm">
                                            {new Date(estimate.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center justify-end gap-1">
                                                {(estimate.status === 'draft' || estimate.status === 'sent') && (
                                                    <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                        <Link href={route('estimates.edit', estimate.id)}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                )}
                                                {(estimate.status === 'draft' || estimate.status === 'sent') && (
                                                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleDelete(estimate)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {estimates.total > estimates.per_page && (
                    <Pagination
                        currentPage={estimates.current_page}
                        lastPage={estimates.last_page}
                        total={estimates.total}
                        perPage={estimates.per_page}
                        nextPageUrl={estimates.next_page_url}
                        prevPageUrl={estimates.prev_page_url}
                    />
                )}
            </div>
        </AppLayout>
    );
}
