import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { store } from '@/routes/password/change';

export default function ChangePassword() {
    return (
        <>
            <Head title="Change password" />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="current_password">
                                Current password
                            </Label>
                            <PasswordInput
                                id="current_password"
                                name="current_password"
                                placeholder="Current password"
                                autoComplete="current-password"
                                autoFocus
                            />
                            <InputError message={errors.current_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">New password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder="New password"
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Confirm password"
                                autoComplete="new-password"
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="change-password-button"
                            >
                                {processing && <Spinner />}
                                Change password
                            </Button>

                            <Link
                                href={logout()}
                                as="button"
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Log out
                            </Link>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ChangePassword.layout = {
    title: 'Change password',
    description:
        'Your account still uses a temporary password. Choose a new password to continue.',
};
