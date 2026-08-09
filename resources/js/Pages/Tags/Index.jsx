import { Head, usePage } from '@inertiajs/react';
import { App } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import TagList from '../../Components/TagList';

export default function Index() {
    const { props } = usePage();
    return (
        <App>
            <AuthenticatedLayout title="Tag">
                <Head title="Tag" />
                <TagList tags={props.tags} />
            </AuthenticatedLayout>
        </App>
    );
}
