import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button, Form, message, Spin } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import CategoryForm from '../../Components/CategoryForm';
import { apiGet, apiPut } from '../../utils/api';

/**
 * @param {{ id: number|string }} props
 */
export default function Edit({ id }) {
    const [form] = Form.useForm();
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const load = async () => {
            try {
                const [catRes, allRes] = await Promise.all([
                    apiGet(`/api/categories/${id}`),
                    apiGet('/api/categories'),
                ]);
                form.setFieldsValue(catRes.data);
                setCategories(allRes.data || []);
            } catch {
                message.error('Failed to load category');
            } finally {
                setLoading(false);
            }
        };

        load();
    }, [id, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            await apiPut(`/api/categories/${id}`, values);
            message.success('Category updated');
            router.visit('/categories');
        } catch (error) {
            if (error.errors) {
                message.error('Validation failed');
            }
        }
    };

    if (loading) {
        return (
            <AuthenticatedLayout title="Edit Category">
                <Spin />
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout title="Edit Category">
            <Head title="Edit Category" />
            <CategoryForm form={form} categories={categories} initialValues={{ id: Number(id) }} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Update Category
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
