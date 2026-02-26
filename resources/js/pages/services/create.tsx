import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Services', href: '/services' },
    { title: 'Create', href: '/services/create' },
];

export default function CreateService() {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        description: '',
        default_price: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('services.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Service" />

            <div className="mx-auto max-w-2xl space-y-6">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">Add Service</h2>
                    <p className="text-muted-foreground text-sm">Add a new labor service to your catalog</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">
                            Name <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            autoFocus
                            placeholder="Service name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Optional description"
                            rows={3}
                        />
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
                            placeholder="0.00"
                        />
                        <InputError message={errors.default_price} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Service'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('services.index')}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
