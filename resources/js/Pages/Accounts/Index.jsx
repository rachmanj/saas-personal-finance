import { Head } from '@inertiajs/react';
import { App } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import AccountList from '../../Components/AccountList';

export default function Index() {
    return (
        <App>
            <AuthenticatedLayout title="Accounts">
                <Head title="Accounts" />
                <AccountList />
            </AuthenticatedLayout>
        </App>
    );
}
