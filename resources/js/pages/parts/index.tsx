import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Edit, Plus, Search, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface Part {
    id: number;
    sku: string;
    name: string;
    description: string | null;
    cost: string;
    sale_price: string;
    stock: number;
    min_stock: number;
}

interface PaginatedParts {
    data: Part[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    parts: PaginatedParts;
    filters: {
        search: string | null;
        filter: string | null;
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Inventory', href: '/parts' }];

export default function PartsIndex({ parts, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const performSearch = useCallback(
        (value: string) => {
            router.get(
                route('parts.index'),
                { search: value || undefined, filter: filters.filter || undefined },
                { preserveState: true, replace: true },
            );
        },
        [filters.filter],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            performSearch(search);
        }, 300);

        return () => clearTimeout(timer);
    }, [search, performSearch]);

    const toggleLowStockFilter = () => {
        const newFilter = filters.filter === 'low_stock' ? undefined : 'low_stock';
        router.get(route('parts.index'), { search: filters.search || undefined, filter: newFilter }, { preserveState: true, replace: true });
    };

    const isLowStock = (part: Part) => part.stock <= part.min_stock;

    const handleDelete = (part: Part) => {
        if (confirm(`Are you sure you want to delete "${part.name}"? This action cannot be undone.`)) {
            router.delete(route('parts.destroy', part.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">Inventory</h2>
                        <p className="text-muted-foreground text-sm">Manage your parts and supplies</p>
                    </div>
                    <Button asChild>
                        <Link href={route('parts.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Part
                        </Link>
                    </Button>
                </div>

                <div className="flex items-center gap-3">
                    <div className="relative max-w-sm">
                        <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by name or SKU..." className="pl-10" />
                    </div>
                    <Button variant={filters.filter === 'low_stock' ? 'destructive' : 'outline'} size="sm" onClick={toggleLowStockFilter}>
                        <AlertTriangle className="mr-2 h-4 w-4" />
                        Low Stock
                    </Button>
                </div>

                {parts.data.length === 0 ? (
                    <div className="flex min-h-[400px] items-center justify-center rounded-lg border border-dashed">
                        <p className="text-muted-foreground text-sm">
                            {filters.search || filters.filter
                                ? 'No parts found matching your criteria.'
                                : 'No parts yet. Add your first part to get started.'}
                        </p>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>SKU</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead className="text-right">Cost</TableHead>
                                    <TableHead className="text-right">Sale Price</TableHead>
                                    <TableHead className="text-right">Stock</TableHead>
                                    <TableHead className="w-[100px]" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {parts.data.map((part) => (
                                    <TableRow key={part.id}>
                                        <TableCell className="font-mono text-sm">{part.sku}</TableCell>
                                        <TableCell>
                                            <Link href={route('parts.show', part.id)} className="font-medium hover:underline">
                                                {part.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="text-right">${Number(part.cost).toFixed(2)}</TableCell>
                                        <TableCell className="text-right">${Number(part.sale_price).toFixed(2)}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <span>{part.stock}</span>
                                                {isLowStock(part) && (
                                                    <Badge variant="destructive" className="text-xs">
                                                        Low
                                                    </Badge>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center justify-end gap-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                    <Link href={route('parts.edit', part.id)}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleDelete(part)}>
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {parts.total > parts.per_page && (
                    <Pagination
                        currentPage={parts.current_page}
                        lastPage={parts.last_page}
                        total={parts.total}
                        perPage={parts.per_page}
                        nextPageUrl={parts.next_page_url}
                        prevPageUrl={parts.prev_page_url}
                    />
                )}
            </div>
        </AppLayout>
    );
}
