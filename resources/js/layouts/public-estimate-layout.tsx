import AppLogoIcon from '@/components/app-logo-icon';
import { Toaster } from '@/components/ui/sonner';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

interface PublicEstimateLayoutProps {
    children: React.ReactNode;
}

export default function PublicEstimateLayout({ children }: PublicEstimateLayoutProps) {
    const { name, flash } = usePage<SharedData>().props;

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
    }, [flash.success]);

    return (
        <div className="bg-background min-h-screen">
            <header className="border-b">
                <div className="mx-auto flex max-w-4xl items-center gap-3 px-4 py-6 sm:px-6 lg:px-8">
                    <AppLogoIcon className="h-8 w-8 fill-current text-[var(--foreground)] dark:text-white" />
                    <span className="text-xl font-semibold">{name}</span>
                </div>
            </header>
            <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">{children}</main>
            <footer className="border-t py-6">
                <div className="text-muted-foreground mx-auto max-w-4xl px-4 text-center text-sm sm:px-6 lg:px-8">Powered by {name}</div>
            </footer>
            <Toaster position="top-right" richColors />
        </div>
    );
}
