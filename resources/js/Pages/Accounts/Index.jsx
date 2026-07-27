import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import AccountList from '../../Components/AccountList';

export default function Index() {
    return (
        <AuthenticatedLayout title="Accounts">
            <Head title="Accounts" />
            <AccountList />
        </AuthenticatedLayout>
    );
}
