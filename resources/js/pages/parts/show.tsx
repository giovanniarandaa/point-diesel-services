import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Edit, Trash2 } from 'lucide-react';
import { useState } from 'react';

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

export default function ShowPart({ part }: { part: Part }) {
    const [deleting, setDeleting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/parts' },
        { title: part.name, href: `/parts/${part.id}` },
    ];

    const isLowStock = part.stock <= part.min_stock;

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this part? This action cannot be undone.')) {
            setDeleting(true);
            router.delete(route('parts.destroy', part.id), {
                onFinish: () => setDeleting(false),
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={part.name} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h2 className="text-xl font-semibold tracking-tight">{part.name}</h2>
                            {isLowStock && <Badge variant="destructive">Low Stock</Badge>}
                        </div>
                        <p className="text-muted-foreground font-mono text-sm">{part.sku}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('parts.edit', part.id)}>
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

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Pricing</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Cost:</span>
                                <span className="font-medium">${Number(part.cost).toFixed(2)}</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Sale Price:</span>
                                <span className="font-medium">${Number(part.sale_price).toFixed(2)}</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Margin:</span>
                                <span className="font-medium">${(Number(part.sale_price) - Number(part.cost)).toFixed(2)}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Stock</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Current Stock:</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">{part.stock}</span>
                                    {isLowStock && (
                                        <Badge variant="destructive" className="text-xs">
                                            Low
                                        </Badge>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Minimum Stock:</span>
                                <span className="font-medium">{part.min_stock}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {part.description && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm">{part.description}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
