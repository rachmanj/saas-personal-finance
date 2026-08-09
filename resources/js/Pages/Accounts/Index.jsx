import { Head, usePage } from '@inertiajs/react';
import { App } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import AccountList from '../../Components/AccountList';

export default function Index() {
    const { props } = usePage();
    return (
        <App>
            <AuthenticatedLayout title="Akun">
                <Head title="Akun" />
                <AccountList accounts={props.accounts} />
            </AuthenticatedLayout>
        </App>
    );
}
