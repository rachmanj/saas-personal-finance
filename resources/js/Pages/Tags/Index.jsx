import { Head } from '@inertiajs/react';
import { App } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import TagList from '../../Components/TagList';

export default function Index() {
    return (
        <App>
            <AuthenticatedLayout title="Tags">
                <Head title="Tags" />
                <TagList />
            </AuthenticatedLayout>
        </App>
    );
}
