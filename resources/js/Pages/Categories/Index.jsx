import { useEffect, useState, useCallback } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { App, Button } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import CategoryTree from '../../Components/CategoryTree';
import { apiGet } from '../../utils/api';

export default function Index() {
    const { props } = usePage();
    const [categories, setCategories] = useState(props.categories || []);
    const [loading, setLoading] = useState(false);

    const loadCategories = useCallback(async () => {
        setLoading(true);
        try {
            const response = await apiGet('/api/categories');
            setCategories(response.data || []);
        } catch {
            // CategoryTree handles empty state
        } finally {
            setLoading(false);
        }
    }, []);

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
                <CategoryTree categories={categories} onRefresh={loadCategories} loading={loading} />
            </AuthenticatedLayout>
        </App>
    );
}
