import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    units_count: number;
}

interface PaginatedCustomers {
    data: Customer[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    customers: PaginatedCustomers;
    filters: {
        search: string | null;
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Customers', href: '/customers' }];

export default function CustomersIndex({ customers, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const performSearch = useCallback((value: string) => {
        router.get(route('customers.index'), { search: value || undefined }, { preserveState: true, replace: true });
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => {
            performSearch(search);
        }, 300);

        return () => clearTimeout(timer);
    }, [search, performSearch]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customers" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">Customers</h2>
                        <p className="text-muted-foreground text-sm">Manage your customer database</p>
                    </div>
                    <Button asChild>
                        <Link href={route('customers.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Customer
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name, phone, or email..."
                        className="pl-10"
                    />
                </div>

                {customers.data.length === 0 ? (
                    <div className="flex min-h-[400px] items-center justify-center rounded-lg border border-dashed">
                        <p className="text-muted-foreground text-sm">
                            {filters.search
                                ? 'No customers found matching your search.'
                                : 'No customers yet. Add your first customer to get started.'}
                        </p>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Phone</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead className="text-right">Units</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {customers.data.map((customer) => (
                                    <TableRow key={customer.id}>
                                        <TableCell>
                                            <Link href={route('customers.show', customer.id)} className="font-medium hover:underline">
                                                {customer.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{customer.phone ?? <span className="text-muted-foreground">--</span>}</TableCell>
                                        <TableCell>{customer.email ?? <span className="text-muted-foreground">--</span>}</TableCell>
                                        <TableCell className="text-right">{customer.units_count}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {customers.total > customers.per_page && (
                    <Pagination
                        currentPage={customers.current_page}
                        lastPage={customers.last_page}
                        total={customers.total}
                        perPage={customers.per_page}
                        nextPageUrl={customers.next_page_url}
                        prevPageUrl={customers.prev_page_url}
                    />
                )}
            </div>
        </AppLayout>
    );
}
