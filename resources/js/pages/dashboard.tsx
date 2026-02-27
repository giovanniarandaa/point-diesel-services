import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type DashboardStats, type Estimate, type LowStockPart } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Clock, DollarSign, FileText, Receipt } from 'lucide-react';

interface Props {
    stats: DashboardStats;
    recentEstimates: Estimate[];
    lowStockParts: LowStockPart[];
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/' }];

export default function Dashboard({ stats, recentEstimates, lowStockParts }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">Dashboard</h2>
                    <p className="text-muted-foreground text-sm">Overview of your shop operations</p>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Estimates</CardTitle>
                            <FileText className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalEstimates}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Estimates</CardTitle>
                            <Clock className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.activeEstimates}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Invoices This Month</CardTitle>
                            <Receipt className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.invoicesThisMonth}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Revenue This Month</CardTitle>
                            <DollarSign className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                ${Number(stats.revenueThisMonth).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Two-column layout: Recent Estimates + Low Stock */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Recent Estimates */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Recent Estimates</CardTitle>
                                <Link href={route('estimates.index')} className="text-muted-foreground text-sm hover:underline">
                                    View all
                                </Link>
                            </CardHeader>
                            <CardContent>
                                {recentEstimates.length === 0 ? (
                                    <p className="text-muted-foreground py-8 text-center text-sm">No estimates yet.</p>
                                ) : (
                                    <div className="rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Estimate #</TableHead>
                                                    <TableHead>Customer</TableHead>
                                                    <TableHead>Status</TableHead>
                                                    <TableHead className="text-right">Total</TableHead>
                                                    <TableHead className="text-right">Date</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {recentEstimates.map((estimate) => (
                                                    <TableRow key={estimate.id}>
                                                        <TableCell>
                                                            <Link
                                                                href={route('estimates.show', estimate.id)}
                                                                className="font-mono font-medium hover:underline"
                                                            >
                                                                {estimate.estimate_number}
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell>{estimate.customer?.name}</TableCell>
                                                        <TableCell>
                                                            <Badge variant={statusVariants[estimate.status] ?? 'secondary'}>
                                                                {statusLabels[estimate.status] ?? estimate.status}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-right font-medium">${Number(estimate.total).toFixed(2)}</TableCell>
                                                        <TableCell className="text-muted-foreground text-right text-sm">
                                                            {new Date(estimate.created_at).toLocaleDateString()}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Low Stock Items */}
                    <div>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Low Stock Items</CardTitle>
                                <Link href={route('parts.index', { filter: 'low_stock' })} className="text-muted-foreground text-sm hover:underline">
                                    View inventory
                                </Link>
                            </CardHeader>
                            <CardContent>
                                {lowStockParts.length === 0 ? (
                                    <p className="text-muted-foreground py-8 text-center text-sm">All parts are stocked.</p>
                                ) : (
                                    <div className="max-h-[400px] space-y-3 overflow-y-auto">
                                        {lowStockParts.map((part) => (
                                            <div key={part.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div className="min-w-0 flex-1">
                                                    <Link
                                                        href={route('parts.show', part.id)}
                                                        className="block truncate text-sm font-medium hover:underline"
                                                    >
                                                        {part.name}
                                                    </Link>
                                                    <span className="text-muted-foreground font-mono text-xs">{part.sku}</span>
                                                </div>
                                                <div className="ml-3 flex items-center gap-2">
                                                    <span className="text-sm tabular-nums">
                                                        {part.stock}/{part.min_stock}
                                                    </span>
                                                    <AlertTriangle className="h-4 w-4 text-red-500" />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
