import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Business settings',
        href: '/settings/business',
    },
];

interface Props {
    shop_supplies_rate: string;
    tax_rate: string;
}

export default function Business({ shop_supplies_rate, tax_rate }: Props) {
    const { data, setData, patch, errors, processing, recentlySuccessful, transform } = useForm({
        shop_supplies_rate: String(parseFloat(shop_supplies_rate) * 100),
        tax_rate: String(parseFloat(tax_rate) * 100),
    });

    transform((data) => ({
        shop_supplies_rate: String(parseFloat(data.shop_supplies_rate) / 100),
        tax_rate: String(parseFloat(data.tax_rate) / 100),
    }));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('business-settings.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Business settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Business configuration" description="Configure rates used for estimate and invoice calculations" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="shop_supplies_rate">Shop Supplies Rate (%)</Label>
                            <Input
                                id="shop_supplies_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                className="mt-1 block w-full"
                                value={data.shop_supplies_rate}
                                onChange={(e) => setData('shop_supplies_rate', e.target.value)}
                                required
                                placeholder="5.00"
                            />
                            <p className="text-muted-foreground text-sm">Applied to labor subtotal on new estimates</p>
                            <InputError className="mt-2" message={errors.shop_supplies_rate} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="tax_rate">Tax Rate (%)</Label>
                            <Input
                                id="tax_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                className="mt-1 block w-full"
                                value={data.tax_rate}
                                onChange={(e) => setData('tax_rate', e.target.value)}
                                required
                                placeholder="8.25"
                            />
                            <p className="text-muted-foreground text-sm">Applied to total on new estimates</p>
                            <InputError className="mt-2" message={errors.tax_rate} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Saved</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
