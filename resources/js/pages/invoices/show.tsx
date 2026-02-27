import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatLineType } from '@/lib/estimate-helpers';
import { type BreadcrumbItem, type Invoice } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, Download, FileText, Truck } from 'lucide-react';
import { useState } from 'react';

interface Props {
    invoice: Invoice;
}

export default function ShowInvoice({ invoice }: Props) {
    const estimate = invoice.estimate;
    const [notifying, setNotifying] = useState(false);

    const handleNotify = () => {
        if (confirm('Notify the customer that their vehicle is ready for pickup?')) {
            setNotifying(true);
            router.post(
                route('invoices.notify', invoice.id),
                {},
                {
                    onFinish: () => setNotifying(false),
                },
            );
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Estimates', href: '/estimates' },
        ...(estimate ? [{ title: estimate.estimate_number, href: `/estimates/${estimate.id}` }] : []),
        { title: invoice.invoice_number, href: `/invoices/${invoice.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={invoice.invoice_number} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div>
                            <h2 className="text-xl font-semibold tracking-tight">{invoice.invoice_number}</h2>
                            <p className="text-muted-foreground text-sm">
                                {estimate?.customer?.name}
                                {estimate?.unit && ` — ${estimate.unit.make} ${estimate.unit.model}`}
                            </p>
                        </div>
                        <Badge variant="outline">Invoiced</Badge>
                    </div>
                    <div className="flex items-center gap-2">
                        {!invoice.notified_at ? (
                            <Button variant="default" size="sm" onClick={handleNotify} disabled={notifying}>
                                <Truck className="mr-2 h-4 w-4" />
                                {notifying ? 'Notifying...' : 'Vehicle Ready'}
                            </Button>
                        ) : (
                            <Badge variant="outline" className="gap-1 border-green-300 text-green-700 dark:border-green-700 dark:text-green-400">
                                <CheckCircle className="h-3 w-3" />
                                Notified {new Date(invoice.notified_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                            </Badge>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <a href={route('invoices.pdf', invoice.id)} download>
                                <Download className="mr-2 h-4 w-4" />
                                Download PDF
                            </a>
                        </Button>
                        {estimate && (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={route('estimates.show', estimate.id)}>
                                    <FileText className="mr-2 h-4 w-4" />
                                    View Estimate
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Invoice Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Invoice Number</span>
                                <span className="font-medium">{invoice.invoice_number}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Date Issued</span>
                                <span>
                                    {new Date(invoice.issued_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                                </span>
                            </div>
                            {estimate && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Estimate</span>
                                    <Link href={route('estimates.show', estimate.id)} className="text-primary hover:underline">
                                        {estimate.estimate_number}
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Customer & Vehicle</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            {estimate?.customer && (
                                <>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Customer</span>
                                        <span className="font-medium">{estimate.customer.name}</span>
                                    </div>
                                    {estimate.customer.phone && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Phone</span>
                                            <span>{estimate.customer.phone}</span>
                                        </div>
                                    )}
                                </>
                            )}
                            {estimate?.unit && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Vehicle</span>
                                    <span>
                                        {estimate.unit.make} {estimate.unit.model}
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold">Line Items</h3>
                    {estimate?.lines && estimate.lines.length > 0 ? (
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead className="text-right">Qty</TableHead>
                                        <TableHead className="text-right">Unit Price</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {estimate.lines.map((line) => (
                                        <TableRow key={line.id}>
                                            <TableCell>
                                                <span className="text-muted-foreground text-xs">{formatLineType(line.lineable_type)}</span>
                                            </TableCell>
                                            <TableCell className="font-medium">{line.description}</TableCell>
                                            <TableCell className="text-right">{line.quantity}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(line.unit_price)}</TableCell>
                                            <TableCell className="text-right font-medium">{formatCurrency(line.line_total)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    ) : (
                        <div className="flex min-h-[200px] items-center justify-center rounded-lg border border-dashed">
                            <p className="text-muted-foreground text-sm">No line items.</p>
                        </div>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Totals</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Subtotal Parts</span>
                            <span>{formatCurrency(invoice.subtotal_parts)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Subtotal Labor</span>
                            <span>{formatCurrency(invoice.subtotal_labor)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Shop Supplies ({(Number(invoice.shop_supplies_rate) * 100).toFixed(0)}%)</span>
                            <span>{formatCurrency(invoice.shop_supplies_amount)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Tax ({(Number(invoice.tax_rate) * 100).toFixed(2)}%)</span>
                            <span>{formatCurrency(invoice.tax_amount)}</span>
                        </div>
                        <Separator />
                        <div className="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{formatCurrency(invoice.total)}</span>
                        </div>
                    </CardContent>
                </Card>

                {estimate?.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm">{estimate.notes}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
