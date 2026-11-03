import { Clock3 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

export function SystemClock() {
    const [currentTime, setCurrentTime] = useState<Date | null>(null);
    const timeZone = useMemo(
        () => Intl.DateTimeFormat().resolvedOptions().timeZone,
        [],
    );

    useEffect(() => {
        const updateTime = () => setCurrentTime(new Date());

        updateTime();
        const interval = window.setInterval(updateTime, 1000);

        return () => window.clearInterval(interval);
    }, []);

    const formattedTime = currentTime
        ? new Intl.DateTimeFormat([], {
              hour: 'numeric',
              minute: '2-digit',
              second: '2-digit',
          }).format(currentTime)
        : '—:—:—';

    const formattedDate = currentTime
        ? new Intl.DateTimeFormat([], {
              weekday: 'short',
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          }).format(currentTime)
        : 'Loading local time';

    return (
        <div
            className="grid grid-cols-[auto_1fr] items-center gap-x-3 rounded-lg border border-sidebar-border/70 bg-sidebar-accent/40 px-3 py-2 group-data-[collapsible=icon]:hidden"
            aria-label="System clock"
        >
            <Clock3 className="size-4 text-sidebar-foreground/70" />
            <div className="min-w-0">
                <time
                    className="block font-mono text-sm font-semibold text-sidebar-foreground tabular-nums"
                    dateTime={currentTime?.toISOString()}
                >
                    {formattedTime}
                </time>
                <p className="truncate text-xs text-sidebar-foreground/65">
                    {formattedDate}
                </p>
                <p className="truncate text-[10px] text-sidebar-foreground/50">
                    {timeZone || 'Local time'}
                </p>
            </div>
        </div>
    );
}
