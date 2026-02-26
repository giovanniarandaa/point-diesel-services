import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

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

export default function EditPart({ part }: { part: Part }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/parts' },
        { title: part.name, href: `/parts/${part.id}` },
        { title: 'Edit', href: `/parts/${part.id}/edit` },
    ];

    const { data, setData, patch, errors, processing } = useForm({
        sku: part.sku,
        name: part.name,
        description: part.description ?? '',
        cost: part.cost,
        sale_price: part.sale_price,
        stock: String(part.stock),
        min_stock: String(part.min_stock),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('parts.update', part.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${part.name}`} />

            <div className="mx-auto max-w-2xl space-y-6">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">Edit Part</h2>
                    <p className="text-muted-foreground text-sm">Update part information</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="sku">
                                SKU <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="sku"
                                value={data.sku}
                                onChange={(e) => setData('sku', e.target.value.toUpperCase())}
                                required
                                autoFocus
                                className="font-mono uppercase"
                            />
                            <InputError message={errors.sku} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Name <span className="text-destructive">*</span>
                            </Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                            <InputError message={errors.name} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="cost">
                                Cost ($) <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="cost"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.cost}
                                onChange={(e) => setData('cost', e.target.value)}
                                required
                            />
                            <InputError message={errors.cost} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="sale_price">
                                Sale Price ($) <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="sale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.sale_price}
                                onChange={(e) => setData('sale_price', e.target.value)}
                                required
                            />
                            <InputError message={errors.sale_price} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="stock">
                                Stock <span className="text-destructive">*</span>
                            </Label>
                            <Input id="stock" type="number" min="0" value={data.stock} onChange={(e) => setData('stock', e.target.value)} required />
                            <InputError message={errors.stock} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="min_stock">
                                Minimum Stock <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="min_stock"
                                type="number"
                                min="0"
                                value={data.min_stock}
                                onChange={(e) => setData('min_stock', e.target.value)}
                                required
                            />
                            <InputError message={errors.min_stock} />
                            <p className="text-muted-foreground text-xs">Alert when stock falls to or below this level</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('parts.show', part.id)}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
