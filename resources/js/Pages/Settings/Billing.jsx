import { Head, usePage } from '@inertiajs/react';
import { Alert, App } from 'antd';
import { useEffect } from 'react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import BillingSettings from '../../Components/Settings/BillingSettings';

/**
 * @param {{
 *   subscription: {
 *     tier: string;
 *     subscribed: boolean;
 *     onGracePeriod: boolean;
 *     endsAt: string | null;
 *     hasStripeCustomer: boolean;
 *   };
 *   plans: Record<string, { name: string; price: number; features: string[] }>;
 * }} props
 */
function BillingContent({ subscription, plans }) {
    const { flash } = usePage().props;
    const { message } = App.useApp();

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);

        if (params.get('checkout') === 'success') {
            message.success('Subscription activated! Welcome to Pro.');
        }

        if (params.get('checkout') === 'cancelled') {
            message.info('Checkout was cancelled.');
        }
    }, [message]);

    return (
        <AuthenticatedLayout title="Settings">
            <Head title="Billing" />

            {flash?.error && (
                <Alert type="error" message={flash.error} showIcon style={{ marginBottom: 16 }} />
            )}

            {flash?.success && (
                <Alert type="success" message={flash.success} showIcon style={{ marginBottom: 16 }} />
            )}

            <BillingSettings subscription={subscription} plans={plans} />
        </AuthenticatedLayout>
    );
}

export default function Billing(props) {
    return (
        <App>
            <BillingContent {...props} />
        </App>
    );
}
