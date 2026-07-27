import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { App, Button } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import CategoryTree from '../../Components/CategoryTree';
import { apiGet } from '../../utils/api';

export default function Index() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);

    const loadCategories = async () => {
        setLoading(true);
        try {
            const response = await apiGet('/api/categories');
            setCategories(response.data || []);
        } catch {
            // CategoryTree handles empty state
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadCategories();
    }, []);

    return (
        <App>
            <AuthenticatedLayout title="Categories">
                <Head title="Categories" />
                <div style={{ marginBottom: 16 }}>
                    <Link href="/categories/create">
                        <Button type="primary" icon={<PlusOutlined />}>
                            Create Category
                        </Button>
                    </Link>
                </div>
                <CategoryTree categories={categories} onRefresh={loadCategories} loading={loading} />
            </AuthenticatedLayout>
        </App>
    );
}
