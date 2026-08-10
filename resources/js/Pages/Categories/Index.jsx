import { Head, Link, usePage } from '@inertiajs/react';
import { App, Button } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import CategoryTree from '../../Components/CategoryTree';

export default function Index() {
    const { props } = usePage();
    const categories = props.categories || [];

    return (
        <App>
            <AuthenticatedLayout title="Kategori">
                <Head title="Kategori" />
                <div style={{ marginBottom: 16 }}>
                    <Link href="/categories/create">
                        <Button type="primary" icon={<PlusOutlined />}>
                            Buat Kategori
                        </Button>
                    </Link>
                </div>
                <CategoryTree categories={categories} />
            </AuthenticatedLayout>
        </App>
    );
}
