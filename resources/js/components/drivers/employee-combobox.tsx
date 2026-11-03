import { useEffect, useId, useState } from 'react';
import { employees as searchEmployees } from '@/actions/App/Http/Controllers/DriverController';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { HrisEmployee } from '@/types/driver';

export function EmployeeCombobox({
    onSelect,
}: {
    onSelect: (employee: HrisEmployee | 'outsourced' | null) => void;
}) {
    const listId = useId();
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [results, setResults] = useState<HrisEmployee[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [highlight, setHighlight] = useState(-1);
    const [outsourced, setOutsourced] = useState(false);

    useEffect(() => {
        if (!open || outsourced || query.trim().length < 2) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setLoading(true);
            setError(null);

            try {
                const response = await fetch(
                    searchEmployees.url({ query: { search: query.trim() } }),
                    {
                        signal: controller.signal,
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        response.status === 503
                            ? 'HRIS is not connected or is unavailable. Employee selection will be available once the HRIS connection is configured.'
                            : 'Employee search failed. Please try again.',
                    );
                }

                const data = (await response.json()) as {
                    employees: HrisEmployee[];
                };

                if (!controller.signal.aborted) {
                    setResults(data.employees);
                }
            } catch (failure) {
                if (!controller.signal.aborted) {
                    setError(
                        failure instanceof Error
                            ? failure.message
                            : 'Employee search failed.',
                    );
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }, 300);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [query, open, outsourced]);

    function choose(employee: HrisEmployee) {
        if (employee.is_registered) {
            return;
        }

        onSelect(employee);
        setOutsourced(false);
        setQuery(employee.employee_name);
        setOpen(false);
        setLoading(false);
        setHighlight(-1);
    }

    function chooseOutsourced() {
        onSelect('outsourced');
        setOutsourced(true);
        setQuery('Outsourced driver');
        setResults([]);
        setError(null);
        setOpen(false);
        setLoading(false);
        setHighlight(-1);
    }

    return (
        <div className="relative grid gap-2">
            <Label htmlFor={listId + '-input'}>
                Employee or outsourced driver
            </Label>
            <Input
                id={listId + '-input'}
                role="combobox"
                aria-autocomplete="list"
                aria-expanded={open}
                aria-controls={listId}
                aria-activedescendant={
                    open && (highlight === 0 || results[highlight - 1])
                        ? listId + '-' + highlight
                        : undefined
                }
                autoComplete="off"
                maxLength={100}
                value={query}
                placeholder="Search employee name or employee number"
                onChange={(event) => {
                    setOutsourced(false);
                    setQuery(event.target.value);
                    setResults([]);
                    setError(null);
                    setHighlight(-1);
                    setOpen(true);
                    setLoading(event.target.value.trim().length >= 2);
                    onSelect(null);
                }}
                onFocus={() => setOpen(true)}
                onBlur={() => setOpen(false)}
                onKeyDown={(event) => {
                    if (event.key === 'Escape') {
                        setOpen(false);
                    }

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        setOpen(true);
                        setHighlight((current) =>
                            Math.min(current + 1, results.length),
                        );
                    }

                    if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        setHighlight((current) => Math.max(current - 1, 0));
                    }

                    if (event.key === 'Enter' && open) {
                        event.preventDefault();

                        if (highlight === 0) {
                            chooseOutsourced();
                        } else if (results[highlight - 1]) {
                            choose(results[highlight - 1]);
                        }
                    }
                }}
            />
            {open && (
                <div className="absolute top-full z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
                    <ul
                        role="listbox"
                        id={listId}
                        aria-label="Employee or outsourced driver"
                    >
                        <li
                            id={listId + '-0'}
                            role="option"
                            aria-selected={highlight === 0}
                            className={
                                'cursor-pointer rounded-sm border-b px-3 py-3 text-sm ' +
                                (highlight === 0
                                    ? 'bg-accent'
                                    : 'hover:bg-accent')
                            }
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={chooseOutsourced}
                            onMouseEnter={() => setHighlight(0)}
                        >
                            <p className="font-medium">Outsourced driver</p>
                            <p className="text-xs text-muted-foreground">
                                Enter all profile details manually. No HRIS
                                record needed.
                            </p>
                        </li>
                        {results.map((employee, index) => (
                            <li
                                key={employee.id}
                                id={listId + '-' + (index + 1)}
                                role="option"
                                aria-selected={index + 1 === highlight}
                                aria-disabled={employee.is_registered}
                                className={
                                    'cursor-pointer rounded-sm px-3 py-2 text-sm ' +
                                    (employee.is_registered
                                        ? 'cursor-not-allowed opacity-50'
                                        : index + 1 === highlight
                                          ? 'bg-accent'
                                          : 'hover:bg-accent')
                                }
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => choose(employee)}
                                onMouseEnter={() => setHighlight(index + 1)}
                            >
                                <p className="font-medium">
                                    {employee.employee_name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {employee.employee_number || employee.id} ·{' '}
                                    {employee.department ||
                                        'Department not recorded'}
                                    {employee.is_registered
                                        ? ' · Already a driver'
                                        : ''}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <div
                        role="status"
                        className="text-sm text-muted-foreground"
                    >
                        {outsourced || query.trim().length < 2 ? (
                            <p className="p-3">
                                Type at least two characters to search HRIS
                                employees.
                            </p>
                        ) : loading ? (
                            <p className="animate-pulse p-3">
                                Searching employees…
                            </p>
                        ) : error ? (
                            <p className="p-3 text-destructive">{error}</p>
                        ) : results.length === 0 ? (
                            <p className="p-3">
                                No matching HRIS employees found.
                            </p>
                        ) : null}
                    </div>
                </div>
            )}
        </div>
    );
}
