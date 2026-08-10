import { Head, usePage, router } from '@inertiajs/react';
import { Button, Form, message } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import CategoryForm from '../../Components/CategoryForm';

export default function Create() {
    const { props } = usePage();
    const [form] = Form.useForm();
    const parentCategories = props.parentCategories || [];

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            router.post('/categories', values);
        } catch (error) {
            if (error.errorFields) {
                message.error('Mohon isi field yang diperlukan');
            }
        }
    };

    return (
        <AuthenticatedLayout title="Buat Kategori">
            <Head title="Buat Kategori" />
            <CategoryForm form={form} categories={parentCategories} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Simpan
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
