import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    total: number;
    perPage: number;
    nextPageUrl: string | null;
    prevPageUrl: string | null;
}

export default function Pagination({ currentPage, lastPage, total, perPage, nextPageUrl, prevPageUrl }: PaginationProps) {
    const from = (currentPage - 1) * perPage + 1;
    const to = Math.min(currentPage * perPage, total);

    return (
        <div className="flex items-center justify-between px-2">
            <p className="text-muted-foreground text-sm">
                Showing {from} to {to} of {total} results
            </p>
            <div className="flex items-center space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!prevPageUrl}
                    onClick={() => prevPageUrl && router.get(prevPageUrl, {}, { preserveState: true })}
                >
                    <ChevronLeft className="h-4 w-4" />
                    Previous
                </Button>
                <span className="text-sm">
                    Page {currentPage} of {lastPage}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!nextPageUrl}
                    onClick={() => nextPageUrl && router.get(nextPageUrl, {}, { preserveState: true })}
                >
                    Next
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}
