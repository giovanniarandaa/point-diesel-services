import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatLineType, STATUS_LABELS, STATUS_VARIANTS } from '@/lib/estimate-helpers';
import { type BreadcrumbItem, type Estimate, type StockWarning } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Copy, Edit, FileText, Receipt, Send, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
    estimate: Estimate;
}

export default function ShowEstimate({ estimate }: Props) {
    const [deleting, setDeleting] = useState(false);
    const [sending, setSending] = useState(false);
    const [converting, setConverting] = useState(false);
    const [copied, setCopied] = useState(false);
    const [stockWarnings, setStockWarnings] = useState<StockWarning[] | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Estimates', href: '/estimates' },
        { title: estimate.estimate_number, href: `/estimates/${estimate.id}` },
    ];

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this estimate? This action cannot be undone.')) {
            setDeleting(true);
            router.delete(route('estimates.destroy', estimate.id), {
                onFinish: () => setDeleting(false),
            });
        }
    };

    const handleSend = () => {
        if (confirm('Send this estimate? A public link will be generated for your customer.')) {
            setSending(true);
            router.post(
                route('estimates.send', estimate.id),
                {},
                {
                    onFinish: () => setSending(false),
                },
            );
        }
    };

    const handleCopyLink = async () => {
        if (estimate.public_token) {
            const url = `${window.location.origin}/estimate/${estimate.public_token}`;
            await navigator.clipboard.writeText(url);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const handleConvertToInvoice = async () => {
        try {
            const response = await fetch(route('api.stock-warnings', estimate.id));
            const warnings: StockWarning[] = await response.json();

            if (warnings.length > 0) {
                setStockWarnings(warnings);
                return;
            }

            confirmConversion();
        } catch {
            confirmConversion();
        }
    };

    const confirmConversion = () => {
        if (confirm('Convert this estimate to an invoice? This will deduct parts from inventory and cannot be undone.')) {
            setConverting(true);
            setStockWarnings(null);
            router.post(
                route('invoices.store', estimate.id),
                {},
                {
                    onFinish: () => setConverting(false),
                },
            );
        }
    };

    const handleForceConversion = () => {
        setConverting(true);
        setStockWarnings(null);
        router.post(
            route('invoices.store', estimate.id),
            {},
            {
                onFinish: () => setConverting(false),
            },
        );
    };

    const canEdit = estimate.status === 'draft' || estimate.status === 'sent';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={estimate.estimate_number} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div>
                            <h2 className="text-xl font-semibold tracking-tight">{estimate.estimate_number}</h2>
                            <p className="text-muted-foreground text-sm">
                                {estimate.customer?.name}
                                {estimate.unit && ` — ${estimate.unit.make} ${estimate.unit.model}`}
                            </p>
                        </div>
                        <Badge variant={STATUS_VARIANTS[estimate.status] ?? 'secondary'}>{STATUS_LABELS[estimate.status] ?? estimate.status}</Badge>
                    </div>
                    <div className="flex items-center gap-2">
                        {estimate.status === 'draft' && (
                            <Button variant="outline" size="sm" onClick={handleSend} disabled={sending}>
                                <Send className="mr-2 h-4 w-4" />
                                {sending ? 'Sending...' : 'Send'}
                            </Button>
                        )}
                        {estimate.public_token && (
                            <Button variant="outline" size="sm" onClick={handleCopyLink}>
                                <Copy className="mr-2 h-4 w-4" />
                                {copied ? 'Copied!' : 'Copy Link'}
                            </Button>
                        )}
                        {estimate.status === 'approved' && !estimate.invoice && (
                            <Button variant="default" size="sm" onClick={handleConvertToInvoice} disabled={converting}>
                                <Receipt className="mr-2 h-4 w-4" />
                                {converting ? 'Converting...' : 'Convert to Invoice'}
                            </Button>
                        )}
                        {estimate.invoice && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('invoices.show', estimate.invoice.id)}>
                                    <FileText className="mr-2 h-4 w-4" />
                                    View Invoice
                                </Link>
                            </Button>
                        )}
                        {canEdit && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('estimates.edit', estimate.id)}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Edit
                                </Link>
                            </Button>
                        )}
                        {canEdit && (
                            <Button variant="destructive" size="sm" onClick={handleDelete} disabled={deleting}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                {deleting ? 'Deleting...' : 'Delete'}
                            </Button>
                        )}
                    </div>
                </div>

                {stockWarnings && stockWarnings.length > 0 && (
                    <Card className="border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-950">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
                                <AlertTriangle className="h-5 w-5" />
                                Insufficient Stock Warning
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <p className="text-sm text-yellow-700 dark:text-yellow-300">
                                The following parts have insufficient stock. Converting will result in negative inventory:
                            </p>
                            <ul className="space-y-1 text-sm">
                                {stockWarnings.map((w) => (
                                    <li key={w.part_id} className="text-yellow-800 dark:text-yellow-200">
                                        <span className="font-medium">{w.name}</span> ({w.sku}) — Need {w.requested}, have {w.available}
                                    </li>
                                ))}
                            </ul>
                            <div className="flex gap-2 pt-2">
                                <Button size="sm" variant="outline" onClick={handleForceConversion} disabled={converting}>
                                    {converting ? 'Converting...' : 'Convert Anyway'}
                                </Button>
                                <Button size="sm" variant="ghost" onClick={() => setStockWarnings(null)}>
                                    Cancel
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {estimate.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm">{estimate.notes}</p>
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold">Line Items</h3>
                    {estimate.lines && estimate.lines.length > 0 ? (
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
                            <span>{formatCurrency(estimate.subtotal_parts)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Subtotal Labor</span>
                            <span>{formatCurrency(estimate.subtotal_labor)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Shop Supplies ({(Number(estimate.shop_supplies_rate) * 100).toFixed(0)}%)</span>
                            <span>{formatCurrency(estimate.shop_supplies_amount)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Tax ({(Number(estimate.tax_rate) * 100).toFixed(2)}%)</span>
                            <span>{formatCurrency(estimate.tax_amount)}</span>
                        </div>
                        <Separator />
                        <div className="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{formatCurrency(estimate.total)}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
