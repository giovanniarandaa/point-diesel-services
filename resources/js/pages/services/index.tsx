import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Edit, Plus, Search, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface LaborService {
    id: number;
    name: string;
    description: string | null;
    default_price: string;
}

interface PaginatedServices {
    data: LaborService[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    services: PaginatedServices;
    filters: {
        search: string | null;
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Services', href: '/services' }];

export default function ServicesIndex({ services, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const performSearch = useCallback((value: string) => {
        router.get(route('services.index'), { search: value || undefined }, { preserveState: true, replace: true });
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => {
            performSearch(search);
        }, 300);

        return () => clearTimeout(timer);
    }, [search, performSearch]);

    const handleDelete = (service: LaborService) => {
        if (confirm(`Are you sure you want to delete "${service.name}"? This action cannot be undone.`)) {
            router.delete(route('services.destroy', service.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Services" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">Services</h2>
                        <p className="text-muted-foreground text-sm">Manage your labor services catalog</p>
                    </div>
                    <Button asChild>
                        <Link href={route('services.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Service
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                    <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by name..." className="pl-10" />
                </div>

                {services.data.length === 0 ? (
                    <div className="flex min-h-[400px] items-center justify-center rounded-lg border border-dashed">
                        <p className="text-muted-foreground text-sm">
                            {filters.search ? 'No services found matching your search.' : 'No services yet. Add your first service to get started.'}
                        </p>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-right">Default Price</TableHead>
                                    <TableHead className="w-[100px]" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {services.data.map((service) => (
                                    <TableRow key={service.id}>
                                        <TableCell>
                                            <Link href={route('services.show', service.id)} className="font-medium hover:underline">
                                                {service.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground max-w-md truncate">
                                            {service.description ?? <span className="text-muted-foreground">--</span>}
                                        </TableCell>
                                        <TableCell className="text-right">${Number(service.default_price).toFixed(2)}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center justify-end gap-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                    <Link href={route('services.edit', service.id)}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleDelete(service)}>
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

                {services.total > services.per_page && (
                    <Pagination
                        currentPage={services.current_page}
                        lastPage={services.last_page}
                        total={services.total}
                        perPage={services.per_page}
                        nextPageUrl={services.next_page_url}
                        prevPageUrl={services.prev_page_url}
                    />
                )}
            </div>
        </AppLayout>
    );
}
