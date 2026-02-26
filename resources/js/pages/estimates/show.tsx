import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Estimate } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Copy, Edit, Send, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
    estimate: Estimate;
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

export default function ShowEstimate({ estimate }: Props) {
    const [deleting, setDeleting] = useState(false);
    const [sending, setSending] = useState(false);
    const [copied, setCopied] = useState(false);

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
                        <Badge variant={statusVariants[estimate.status] ?? 'secondary'}>{statusLabels[estimate.status] ?? estimate.status}</Badge>
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
                                                <span className="text-muted-foreground text-xs">
                                                    {line.lineable_type.includes('Part') ? 'Part' : 'Service'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="font-medium">{line.description}</TableCell>
                                            <TableCell className="text-right">{line.quantity}</TableCell>
                                            <TableCell className="text-right">${Number(line.unit_price).toFixed(2)}</TableCell>
                                            <TableCell className="text-right font-medium">${Number(line.line_total).toFixed(2)}</TableCell>
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
                            <span>${Number(estimate.subtotal_parts).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Subtotal Labor</span>
                            <span>${Number(estimate.subtotal_labor).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Shop Supplies ({(Number(estimate.shop_supplies_rate) * 100).toFixed(0)}%)</span>
                            <span>${Number(estimate.shop_supplies_amount).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Tax ({(Number(estimate.tax_rate) * 100).toFixed(2)}%)</span>
                            <span>${Number(estimate.tax_amount).toFixed(2)}</span>
                        </div>
                        <Separator />
                        <div className="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>${Number(estimate.total).toFixed(2)}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
