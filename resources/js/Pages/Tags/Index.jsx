import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import TagList from '../../Components/TagList';

export default function Index() {
    return (
        <AuthenticatedLayout title="Tags">
            <Head title="Tags" />
            <TagList />
        </AuthenticatedLayout>
    );
}
