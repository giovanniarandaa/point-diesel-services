import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface LaborService {
    id: number;
    name: string;
    description: string | null;
    default_price: string;
}

export default function EditService({ service }: { service: LaborService }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Services', href: '/services' },
        { title: service.name, href: `/services/${service.id}` },
        { title: 'Edit', href: `/services/${service.id}/edit` },
    ];

    const { data, setData, patch, errors, processing } = useForm({
        name: service.name,
        description: service.description ?? '',
        default_price: service.default_price,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('services.update', service.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${service.name}`} />

            <div className="mx-auto max-w-2xl space-y-6">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">Edit Service</h2>
                    <p className="text-muted-foreground text-sm">Update service information</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">
                            Name <span className="text-destructive">*</span>
                        </Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required autoFocus />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="default_price">
                            Default Price ($) <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="default_price"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.default_price}
                            onChange={(e) => setData('default_price', e.target.value)}
                            required
                        />
                        <InputError message={errors.default_price} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('services.show', service.id)}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
