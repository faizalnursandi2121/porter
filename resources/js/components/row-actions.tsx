import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreHorizontal } from 'lucide-react';
import type { ReactNode } from 'react';

type Action = {
    label: string;
    icon?: ReactNode;
    onSelect: () => void;
    destructive?: boolean;
    hidden?: boolean;
};

type Props = {
    /** Screen-reader label, e.g. "Open actions for {name}". */
    label: string;
    actions: Action[];
};

/**
 * Table row action pattern (PRD #16/#17): every row's actions live behind
 * one "..." trigger. Destructive items sit last, separated.
 */
export function RowActions({ label, actions }: Props) {
    const visible = actions.filter((action) => !action.hidden);

    if (visible.length === 0) {
        return null;
    }

    const lastNonDestructive = visible.reduce(
        (last, action, index) => (action.destructive ? last : index),
        -1,
    );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon-sm">
                    <MoreHorizontal className="size-4" />
                    <span className="sr-only">{label}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {visible.map((action, index) => (
                    <span key={action.label} className="contents">
                        {action.destructive &&
                            lastNonDestructive === index - 1 && (
                                <DropdownMenuSeparator />
                            )}
                        <DropdownMenuItem
                            onSelect={(event) => {
                                event.preventDefault();
                                action.onSelect();
                            }}
                            className={
                                action.destructive
                                    ? 'text-destructive focus:text-destructive'
                                    : undefined
                            }
                        >
                            {action.icon}
                            {action.label}
                        </DropdownMenuItem>
                    </span>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
