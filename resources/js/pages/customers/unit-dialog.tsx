import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { type FormEventHandler, type ReactNode, useState } from 'react';

interface Unit {
    id: number;
    vin: string;
    make: string;
    model: string;
    engine: string | null;
    mileage: number;
}

interface UnitDialogProps {
    customerId: number;
    unit?: Unit;
    mode: 'create' | 'edit';
    children: ReactNode;
}

export default function UnitDialog({ customerId, unit, mode, children }: UnitDialogProps) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, patch, errors, processing, reset } = useForm({
        customer_id: customerId,
        vin: unit?.vin ?? '',
        make: unit?.make ?? '',
        model: unit?.model ?? '',
        engine: unit?.engine ?? '',
        mileage: unit?.mileage ?? 0,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);
                if (mode === 'create') {
                    reset();
                }
            },
        };

        if (mode === 'create') {
            post(route('units.store'), options);
        } else if (unit) {
            patch(route('units.update', unit.id), options);
        }
    };

    const handleOpenChange = (newOpen: boolean) => {
        setOpen(newOpen);
        if (!newOpen && mode === 'create') {
            reset();
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-w-2xl">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{mode === 'create' ? 'Add Unit' : 'Edit Unit'}</DialogTitle>
                        <DialogDescription>
                            {mode === 'create' ? 'Add a new vehicle to this customer.' : 'Update vehicle information.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="vin">
                                VIN <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="vin"
                                value={data.vin}
                                onChange={(e) => setData('vin', e.target.value.toUpperCase())}
                                maxLength={17}
                                placeholder="1HGBH41JXMN109186"
                                className="font-mono"
                                required
                            />
                            <InputError message={errors.vin} />
                            <p className="text-muted-foreground text-xs">17 characters (A-Z, 0-9, no I/O/Q)</p>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="make">
                                    Make <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="make"
                                    value={data.make}
                                    onChange={(e) => setData('make', e.target.value)}
                                    placeholder="Freightliner"
                                    required
                                />
                                <InputError message={errors.make} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="model">
                                    Model <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="model"
                                    value={data.model}
                                    onChange={(e) => setData('model', e.target.value)}
                                    placeholder="Cascadia"
                                    required
                                />
                                <InputError message={errors.model} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="engine">Engine</Label>
                            <Input id="engine" value={data.engine} onChange={(e) => setData('engine', e.target.value)} placeholder="Detroit DD15" />
                            <InputError message={errors.engine} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="mileage">
                                Mileage <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="mileage"
                                type="number"
                                min="0"
                                value={data.mileage}
                                onChange={(e) => setData('mileage', parseInt(e.target.value) || 0)}
                                placeholder="0"
                                required
                            />
                            <InputError message={errors.mileage} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={processing}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? (mode === 'create' ? 'Adding...' : 'Saving...') : mode === 'create' ? 'Add Unit' : 'Save Changes'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
