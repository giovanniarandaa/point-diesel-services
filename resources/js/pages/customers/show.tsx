import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import UnitDialog from '@/pages/customers/unit-dialog';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Edit, Mail, Phone, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
}

interface Unit {
    id: number;
    vin: string;
    make: string;
    model: string;
    engine: string | null;
    mileage: number;
}

interface Props {
    customer: Customer;
    units: Unit[];
}

export default function ShowCustomer({ customer, units }: Props) {
    const [deleting, setDeleting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Customers', href: '/customers' },
        { title: customer.name, href: `/customers/${customer.id}` },
    ];

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
            setDeleting(true);
            router.delete(route('customers.destroy', customer.id), {
                onFinish: () => setDeleting(false),
            });
        }
    };

    const handleDeleteUnit = (unit: Unit) => {
        if (confirm(`Are you sure you want to delete the unit ${unit.make} ${unit.model} (${unit.vin})?`)) {
            router.delete(route('units.destroy', unit.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={customer.name} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">{customer.name}</h2>
                        <p className="text-muted-foreground text-sm">Customer details and vehicles</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('customers.edit', customer.id)}>
                                <Edit className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button variant="destructive" size="sm" onClick={handleDelete} disabled={deleting}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            {deleting ? 'Deleting...' : 'Delete'}
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Contact Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {customer.phone && (
                            <div className="flex items-center gap-2">
                                <Phone className="text-muted-foreground h-4 w-4" />
                                <a href={`tel:${customer.phone}`} className="hover:underline">
                                    {customer.phone}
                                </a>
                            </div>
                        )}
                        {customer.email && (
                            <div className="flex items-center gap-2">
                                <Mail className="text-muted-foreground h-4 w-4" />
                                <a href={`mailto:${customer.email}`} className="hover:underline">
                                    {customer.email}
                                </a>
                            </div>
                        )}
                        {!customer.phone && !customer.email && <p className="text-muted-foreground text-sm">No contact information available.</p>}
                    </CardContent>
                </Card>

                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold">Units</h3>
                            <p className="text-muted-foreground text-sm">Vehicles associated with this customer</p>
                        </div>
                        <UnitDialog customerId={customer.id} mode="create">
                            <Button size="sm">
                                <Plus className="mr-2 h-4 w-4" />
                                Add Unit
                            </Button>
                        </UnitDialog>
                    </div>

                    {units.length === 0 ? (
                        <div className="flex min-h-[200px] items-center justify-center rounded-lg border border-dashed">
                            <p className="text-muted-foreground text-sm">No units yet. Add the first vehicle to get started.</p>
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {units.map((unit) => (
                                <Card key={unit.id}>
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <CardTitle className="text-base">
                                                    {unit.make} {unit.model}
                                                </CardTitle>
                                                <CardDescription className="font-mono text-xs">{unit.vin}</CardDescription>
                                            </div>
                                            <div className="flex items-center gap-1">
                                                <UnitDialog customerId={customer.id} unit={unit} mode="edit">
                                                    <Button variant="ghost" size="sm">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </UnitDialog>
                                                <Button variant="ghost" size="sm" onClick={() => handleDeleteUnit(unit)}>
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-2">
                                        {unit.engine && (
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">Engine:</span>
                                                <span>{unit.engine}</span>
                                            </div>
                                        )}
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-muted-foreground">Mileage:</span>
                                            <Badge variant="secondary">{unit.mileage.toLocaleString()} mi</Badge>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
