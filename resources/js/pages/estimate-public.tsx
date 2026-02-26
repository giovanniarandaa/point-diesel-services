import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import PublicEstimateLayout from '@/layouts/public-estimate-layout';
import { formatCurrency, formatLineType, STATUS_LABELS, STATUS_VARIANTS } from '@/lib/estimate-helpers';
import { type Estimate } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, MessageCircle, Phone } from 'lucide-react';
import { useState } from 'react';

interface Props {
    estimate: Estimate;
    shopPhone: string;
}

export default function EstimatePublic({ estimate, shopPhone }: Props) {
    const [approving, setApproving] = useState(false);
    const isAlreadyApproved = estimate.status === 'approved' || estimate.status === 'invoiced';
    const canApprove = estimate.status === 'sent';

    const handleApprove = () => {
        if (confirm('Approve this estimate? This action confirms your acceptance of the quoted work and pricing.')) {
            setApproving(true);
            router.post(route('estimate.public.approve', estimate.public_token!), {}, { onFinish: () => setApproving(false) });
        }
    };

    const cleanPhone = shopPhone.replace(/[^0-9+]/g, '');
    const whatsAppPhone = shopPhone.replace(/[^0-9]/g, '');

    return (
        <PublicEstimateLayout>
            <Head title={`${estimate.estimate_number} - Estimate`} />

            <div className="space-y-6">
                {isAlreadyApproved && (
                    <Alert>
                        <CheckCircle2 className="h-4 w-4" />
                        <AlertTitle>Already Approved</AlertTitle>
                        <AlertDescription>
                            This estimate was approved
                            {estimate.approved_at && ` on ${new Date(estimate.approved_at).toLocaleDateString()}`}. We will contact you soon to
                            schedule the work.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{estimate.estimate_number}</h1>
                        <p className="text-muted-foreground text-sm">
                            {estimate.customer?.name}
                            {estimate.unit && ` — ${estimate.unit.make} ${estimate.unit.model}`}
                        </p>
                    </div>
                    <Badge variant={STATUS_VARIANTS[estimate.status] ?? 'secondary'}>{STATUS_LABELS[estimate.status] ?? estimate.status}</Badge>
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
                        <div className="overflow-x-auto rounded-md border">
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
                        <div className="flex justify-between text-lg font-semibold">
                            <span>Total</span>
                            <span>{formatCurrency(estimate.total)}</span>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex flex-col gap-3 sm:flex-row">
                    {canApprove && (
                        <Button onClick={handleApprove} disabled={approving} size="lg" className="flex-1">
                            <CheckCircle2 className="mr-2 h-5 w-5" />
                            {approving ? 'Approving...' : 'Approve Estimate'}
                        </Button>
                    )}
                    <Button variant="outline" size="lg" className="flex-1" asChild>
                        <a href={`tel:${cleanPhone}`}>
                            <Phone className="mr-2 h-5 w-5" />
                            Call Shop
                        </a>
                    </Button>
                    <Button variant="outline" size="lg" className="flex-1" asChild>
                        <a
                            href={`https://wa.me/${whatsAppPhone}?text=${encodeURIComponent(`Hi, I have a question about estimate ${estimate.estimate_number}`)}`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <MessageCircle className="mr-2 h-5 w-5" />
                            WhatsApp
                        </a>
                    </Button>
                </div>
            </div>
        </PublicEstimateLayout>
    );
}
