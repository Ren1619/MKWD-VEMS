import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/TripController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function TripRequestModal({
    defaultDeparture,
    defaultReturn,
}: {
    defaultDeparture: string;
    defaultReturn: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Request trip</Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Request a trip</DialogTitle>
                    <DialogDescription>
                        Submit the destination, purpose, schedule, and optional
                        passenger manifest for approval.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...store.form()}
                    noValidate
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <fieldset disabled={processing} className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="destination">Destination</Label>
                                <Input
                                    id="destination"
                                    name="destination"
                                    autoFocus
                                />
                                <InputError message={errors.destination} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="purpose">Trip purpose</Label>
                                <textarea
                                    id="purpose"
                                    name="purpose"
                                    rows={3}
                                    className="rounded-md border bg-background p-3 text-sm"
                                />
                                <InputError message={errors.purpose} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="planned_departure">
                                        Planned departure
                                    </Label>
                                    <Input
                                        id="planned_departure"
                                        name="planned_departure"
                                        type="datetime-local"
                                        defaultValue={defaultDeparture}
                                    />
                                    <InputError
                                        message={errors.planned_departure}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="planned_return">
                                        Planned return
                                    </Label>
                                    <Input
                                        id="planned_return"
                                        name="planned_return"
                                        type="datetime-local"
                                        defaultValue={defaultReturn}
                                    />
                                    <InputError
                                        message={errors.planned_return}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="passengers">
                                    Passengers (optional)
                                </Label>
                                <textarea
                                    id="passengers"
                                    name="passengers"
                                    rows={4}
                                    placeholder="One passenger per line"
                                    className="rounded-md border bg-background p-3 text-sm"
                                />
                                <InputError message={errors.passengers} />
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {processing
                                        ? 'Submitting…'
                                        : 'Submit trip request'}
                                </Button>
                            </div>
                        </fieldset>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
