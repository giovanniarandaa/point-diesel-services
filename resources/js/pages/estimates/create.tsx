import CatalogSearch from '@/components/catalog-search';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type CatalogItem, type Customer, type Unit } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { type FormEventHandler, useCallback, useEffect, useState } from 'react';

interface LineItem {
    [key: string]: string | number;
    lineable_type: 'Part' | 'LaborService';
    lineable_id: number;
    description: string;
    quantity: number;
    unit_price: string;
}

interface Props {
    customers: Customer[];
    estimate?: {
        id: number;
        customer_id: number;
        unit_id: number | null;
        notes: string | null;
        lines?: Array<{
            lineable_type: string;
            lineable_id: number;
            description: string;
            quantity: number;
            unit_price: string;
        }>;
    };
}

const SHOP_SUPPLIES_RATE = 0.05;
const TAX_RATE = 0.0825;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Estimates', href: '/estimates' },
    { title: 'Create', href: '/estimates/create' },
];

export default function CreateEstimate({ customers, estimate }: Props) {
    const isEdit = !!estimate;
    const pageErrors = usePage().props.errors as Record<string, string>;

    const initialLines: LineItem[] =
        estimate?.lines?.map((l) => ({
            lineable_type: l.lineable_type.includes('Part') ? ('Part' as const) : ('LaborService' as const),
            lineable_id: l.lineable_id,
            description: l.description,
            quantity: l.quantity,
            unit_price: l.unit_price,
        })) ?? [];

    const [formData, setFormData] = useState({
        customer_id: estimate?.customer_id?.toString() ?? '',
        unit_id: estimate?.unit_id?.toString() ?? '',
        notes: estimate?.notes ?? '',
    });
    const [lines, setLines] = useState<LineItem[]>(initialLines);
    const [processing, setProcessing] = useState(false);

    const setData = (key: string, value: string) => setFormData((prev) => ({ ...prev, [key]: value }));

    const [units, setUnits] = useState<Unit[]>([]);
    const [loadingUnits, setLoadingUnits] = useState(false);

    const loadUnits = useCallback(async (customerId: string) => {
        if (!customerId) {
            setUnits([]);

            return;
        }
        setLoadingUnits(true);
        try {
            const response = await fetch(route('api.customer-units', customerId), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = (await response.json()) as Unit[];
            setUnits(data);
        } catch {
            setUnits([]);
        } finally {
            setLoadingUnits(false);
        }
    }, []);

    useEffect(() => {
        if (formData.customer_id) {
            loadUnits(formData.customer_id);
        }
    }, [formData.customer_id, loadUnits]);

    const handleAddItem = (item: CatalogItem) => {
        const existing = lines.find((l) => l.lineable_type === item.type && l.lineable_id === item.id);

        if (existing) {
            setLines(lines.map((l) => (l.lineable_type === item.type && l.lineable_id === item.id ? { ...l, quantity: l.quantity + 1 } : l)));
        } else {
            setLines([
                ...lines,
                {
                    lineable_type: item.type,
                    lineable_id: item.id,
                    description: item.name,
                    quantity: 1,
                    unit_price: item.price,
                },
            ]);
        }
    };

    const updateLine = (index: number, field: keyof LineItem, value: string | number) => {
        setLines(lines.map((line, i) => (i === index ? { ...line, [field]: value } : line)));
    };

    const removeLine = (index: number) => {
        setLines(lines.filter((_, i) => i !== index));
    };

    const subtotalParts = lines.filter((l) => l.lineable_type === 'Part').reduce((sum, l) => sum + l.quantity * Number(l.unit_price), 0);

    const subtotalLabor = lines.filter((l) => l.lineable_type === 'LaborService').reduce((sum, l) => sum + l.quantity * Number(l.unit_price), 0);

    const shopSupplies = subtotalLabor * SHOP_SUPPLIES_RATE;
    const taxableAmount = subtotalParts + subtotalLabor + shopSupplies;
    const tax = taxableAmount * TAX_RATE;
    const total = taxableAmount + tax;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const payload = { ...formData, lines };
        const options = {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        };

        if (isEdit && estimate) {
            router.put(route('estimates.update', estimate.id), payload, options);
        } else {
            router.post(route('estimates.store'), payload, options);
        }
    };

    const editBreadcrumbs: BreadcrumbItem[] = isEdit
        ? [
              { title: 'Estimates', href: '/estimates' },
              { title: `EST-${String(estimate?.id).padStart(4, '0')}`, href: `/estimates/${estimate?.id}` },
              { title: 'Edit', href: `/estimates/${estimate?.id}/edit` },
          ]
        : breadcrumbs;

    return (
        <AppLayout breadcrumbs={editBreadcrumbs}>
            <Head title={isEdit ? 'Edit Estimate' : 'New Estimate'} />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">{isEdit ? 'Edit Estimate' : 'New Estimate'}</h2>
                    <p className="text-muted-foreground text-sm">
                        {isEdit ? 'Update the estimate details and line items' : 'Create a new estimate for a customer'}
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="customer_id">
                                Customer <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={formData.customer_id}
                                onValueChange={(v) => {
                                    setData('customer_id', v);
                                    setData('unit_id', '');
                                }}
                            >
                                <SelectTrigger id="customer_id">
                                    <SelectValue placeholder="Select customer" />
                                </SelectTrigger>
                                <SelectContent>
                                    {customers.map((c) => (
                                        <SelectItem key={c.id} value={c.id.toString()}>
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={pageErrors.customer_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="unit_id">Unit</Label>
                            <Select
                                value={formData.unit_id}
                                onValueChange={(v) => setData('unit_id', v)}
                                disabled={!formData.customer_id || loadingUnits}
                            >
                                <SelectTrigger id="unit_id">
                                    <SelectValue placeholder={loadingUnits ? 'Loading...' : 'Select unit (optional)'} />
                                </SelectTrigger>
                                <SelectContent>
                                    {units.map((u) => (
                                        <SelectItem key={u.id} value={u.id.toString()}>
                                            {u.make} {u.model} — {u.vin}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={pageErrors.unit_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea
                                id="notes"
                                value={formData.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder="Internal notes..."
                                rows={1}
                            />
                            <InputError message={pageErrors.notes} />
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-semibold">Line Items</h3>
                                <p className="text-muted-foreground text-sm">Add parts and services to this estimate</p>
                            </div>
                            <CatalogSearch onSelect={handleAddItem} />
                        </div>

                        <InputError message={pageErrors.lines} />

                        {lines.length === 0 ? (
                            <div className="flex min-h-[200px] items-center justify-center rounded-lg border border-dashed">
                                <p className="text-muted-foreground text-sm">No items yet. Use the search above to add parts or services.</p>
                            </div>
                        ) : (
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Type</TableHead>
                                            <TableHead className="w-[300px]">Description</TableHead>
                                            <TableHead className="w-[100px]">Qty</TableHead>
                                            <TableHead className="w-[140px]">Unit Price</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                            <TableHead className="w-[50px]" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {lines.map((line, index) => (
                                            <TableRow key={`${line.lineable_type}-${line.lineable_id}`}>
                                                <TableCell>
                                                    <span className="text-muted-foreground text-xs">
                                                        {line.lineable_type === 'Part' ? 'Part' : 'Service'}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="font-medium">{line.description}</TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="1"
                                                        value={line.quantity}
                                                        onChange={(e) => updateLine(index, 'quantity', Math.max(1, parseInt(e.target.value) || 1))}
                                                        className="h-8 w-20"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={line.unit_price}
                                                        onChange={(e) => updateLine(index, 'unit_price', e.target.value)}
                                                        className="h-8 w-28"
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    ${(line.quantity * Number(line.unit_price)).toFixed(2)}
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8"
                                                        onClick={() => removeLine(index)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>

                    {lines.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Totals</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal Parts</span>
                                    <span>${subtotalParts.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal Labor</span>
                                    <span>${subtotalLabor.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Shop Supplies (5%)</span>
                                    <span>${shopSupplies.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Tax (8.25%)</span>
                                    <span>${tax.toFixed(2)}</span>
                                </div>
                                <Separator />
                                <div className="flex justify-between text-base font-semibold">
                                    <span>Total</span>
                                    <span>${total.toFixed(2)}</span>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing || lines.length === 0}>
                            {processing ? 'Saving...' : isEdit ? 'Update Estimate' : 'Create Estimate'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('estimates.index')}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
