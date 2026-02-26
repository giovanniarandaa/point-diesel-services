import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { type CatalogItem } from '@/types';
import { Plus } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface CatalogSearchProps {
    onSelect: (item: CatalogItem) => void;
}

interface CatalogResults {
    parts: CatalogItem[];
    services: CatalogItem[];
}

export default function CatalogSearch({ onSelect }: CatalogSearchProps) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<CatalogResults>({ parts: [], services: [] });
    const [loading, setLoading] = useState(false);
    const abortRef = useRef<AbortController | null>(null);

    const searchCatalog = useCallback(async (q: string) => {
        if (q.length < 2) {
            setResults({ parts: [], services: [] });

            return;
        }

        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        setLoading(true);
        try {
            const response = await fetch(route('api.catalog-search', { q }), {
                signal: controller.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = (await response.json()) as CatalogResults;
            setResults(data);
        } catch {
            // aborted or network error
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => searchCatalog(query), 300);

        return () => clearTimeout(timer);
    }, [query, searchCatalog]);

    const handleSelect = (item: CatalogItem) => {
        onSelect(item);
        setQuery('');
        setResults({ parts: [], services: [] });
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button variant="outline" size="sm" type="button">
                    <Plus className="mr-2 h-4 w-4" />
                    Add Item
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[400px] p-0" align="start">
                <Command shouldFilter={false}>
                    <CommandInput placeholder="Search parts or services..." value={query} onValueChange={setQuery} />
                    <CommandList>
                        {loading && <div className="text-muted-foreground p-4 text-center text-sm">Searching...</div>}
                        {!loading && query.length >= 2 && results.parts.length === 0 && results.services.length === 0 && (
                            <CommandEmpty>No results found.</CommandEmpty>
                        )}
                        {results.parts.length > 0 && (
                            <CommandGroup heading="Parts">
                                {results.parts.map((item) => (
                                    <CommandItem key={`part-${item.id}`} value={`part-${item.id}`} onSelect={() => handleSelect(item)}>
                                        <div className="flex w-full items-center justify-between">
                                            <div>
                                                <span className="font-medium">{item.name}</span>
                                                {item.sku && <span className="text-muted-foreground ml-2 font-mono text-xs">{item.sku}</span>}
                                            </div>
                                            <span className="text-muted-foreground text-sm">${Number(item.price).toFixed(2)}</span>
                                        </div>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                        {results.services.length > 0 && (
                            <CommandGroup heading="Services">
                                {results.services.map((item) => (
                                    <CommandItem key={`service-${item.id}`} value={`service-${item.id}`} onSelect={() => handleSelect(item)}>
                                        <div className="flex w-full items-center justify-between">
                                            <span className="font-medium">{item.name}</span>
                                            <span className="text-muted-foreground text-sm">${Number(item.price).toFixed(2)}</span>
                                        </div>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
