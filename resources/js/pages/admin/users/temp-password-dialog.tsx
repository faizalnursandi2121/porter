import { useEffect, useState } from 'react';
import { Check, Copy, KeyRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';

type Props = {
    /** Plain temporary password — flashed by the server exactly once. */
    tempPassword: string | null;
    /** Called when the dialog is dismissed; clears the local prop. */
    onClose: () => void;
};

/**
 * FR-2/FR-4a: the only moment a temporary password is ever visible. The
 * server stores the hash; once this dialog closes the value is gone, so the
 * copy step is front and center.
 */
export default function TempPasswordDialog({
    tempPassword,
    onClose,
}: Props) {
    const [copiedText, copy] = useClipboard();
    const copied = copiedText === tempPassword && tempPassword !== null;

    return (
        <Dialog open={tempPassword !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <KeyRound className="size-4" />
                        Temporary password
                    </DialogTitle>
                    <DialogDescription>
                        Copy this password now — it is shown only once. The
                        account must change it at next login.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex items-stretch overflow-hidden rounded-lg border border-border">
                    <input
                        type="text"
                        readOnly
                        value={tempPassword ?? ''}
                        data-test="temp-password-value"
                        className="w-full bg-muted/50 p-3 font-mono text-sm text-foreground outline-none"
                    />
                    <button
                        type="button"
                        onClick={() => tempPassword && copy(tempPassword)}
                        data-test="copy-temp-password-button"
                        className="border-l border-border px-3 hover:bg-muted"
                    >
                        {copied ? (
                            <Check className="size-4 text-foreground" />
                        ) : (
                            <Copy className="size-4 text-foreground" />
                        )}
                        <span className="sr-only">Copy temporary password</span>
                    </button>
                </div>

                <DialogFooter>
                    <Button onClick={onClose}>Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/** Convenience for pages: keep the flash value in local state. */
export function useTempPasswordFlash(value: unknown): [string | null, () => void] {
    const [tempPassword, setTempPassword] = useState<string | null>(null);

    useEffect(() => {
        setTempPassword(typeof value === 'string' && value.length > 0 ? value : null);
    }, [value]);

    return [tempPassword, () => setTempPassword(null)];
}
