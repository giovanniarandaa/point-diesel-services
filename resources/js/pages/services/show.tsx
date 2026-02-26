import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Edit, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface LaborService {
    id: number;
    name: string;
    description: string | null;
    default_price: string;
}

export default function ShowService({ service }: { service: LaborService }) {
    const [deleting, setDeleting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Services', href: '/services' },
        { title: service.name, href: `/services/${service.id}` },
    ];

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
            setDeleting(true);
            router.delete(route('services.destroy', service.id), {
                onFinish: () => setDeleting(false),
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={service.name} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">{service.name}</h2>
                        <p className="text-muted-foreground text-sm">Service details</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('services.edit', service.id)}>
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
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">Default Price:</span>
                            <span className="font-medium">${Number(service.default_price).toFixed(2)}</span>
                        </div>
                        {service.description ? (
                            <div className="space-y-1">
                                <span className="text-muted-foreground text-sm">Description:</span>
                                <p className="text-sm">{service.description}</p>
                            </div>
                        ) : (
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Description:</span>
                                <span className="text-muted-foreground">--</span>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
