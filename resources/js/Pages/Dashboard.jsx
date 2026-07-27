import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

export default function Dashboard() {
    return (
        <AuthenticatedLayout title="Dashboard">
            <Head title="Dashboard" />
            <p>Welcome to your Personal Finance Tracker dashboard.</p>
        </AuthenticatedLayout>
    );
}
