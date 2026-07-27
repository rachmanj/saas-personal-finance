import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button, Form, message } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import CategoryForm from '../../Components/CategoryForm';
import { apiGet, apiPost } from '../../utils/api';

export default function Create() {
    const [form] = Form.useForm();
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        apiGet('/api/categories').then((res) => setCategories(res.data || []));
    }, []);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            await apiPost('/api/categories', values);
            message.success('Category created');
            router.visit('/categories');
        } catch (error) {
            if (error.errors) {
                message.error('Validation failed');
            }
        }
    };

    return (
        <AuthenticatedLayout title="Create Category">
            <Head title="Create Category" />
            <CategoryForm form={form} categories={categories} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Create Category
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
