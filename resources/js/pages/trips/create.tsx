import { Form, Head, Link } from '@inertiajs/react';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/TripController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const textareaClass =
    'min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';

export default function TripCreate({
    defaultDeparture,
    defaultReturn,
}: {
    defaultDeparture: string;
    defaultReturn: string;
}) {
    return (
        <>
            <Head title="Request a trip" />
            <div className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Request a trip
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Provide the schedule and travel details for dispatch
                            approval.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Trip request</CardTitle>
                        <CardDescription>
                            A dispatcher will assign an eligible driver and
                            available vehicle when approving the request.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...store.form()}>
                            {({ processing, errors }) => (
                                <fieldset
                                    disabled={processing}
                                    className="grid gap-5"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="destination">
                                            Destination
                                        </Label>
                                        <Input
                                            id="destination"
                                            name="destination"
                                            required
                                            maxLength={255}
                                        />
                                        <InputError
                                            message={errors.destination}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="purpose">
                                            Trip purpose
                                        </Label>
                                        <textarea
                                            id="purpose"
                                            name="purpose"
                                            required
                                            maxLength={5000}
                                            className={textareaClass}
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
                                                required
                                                defaultValue={defaultDeparture}
                                            />
                                            <InputError
                                                message={
                                                    errors.planned_departure
                                                }
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
                                                required
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
                                            maxLength={5000}
                                            className={textareaClass}
                                            placeholder={
                                                'One passenger per line, or include a group description'
                                            }
                                        />
                                        <InputError
                                            message={errors.passengers}
                                        />
                                    </div>
                                    <Button disabled={processing}>
                                        {processing
                                            ? 'Submitting…'
                                            : 'Submit trip request'}
                                    </Button>
                                </fieldset>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
TripCreate.layout = {
    breadcrumbs: [
        { title: 'Trips', href: index() },
        { title: 'Request trip', href: create() },
    ],
};
