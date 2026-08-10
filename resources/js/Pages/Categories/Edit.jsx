import { Head, usePage, router } from '@inertiajs/react';
import { Button, Form, message } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import CategoryForm from '../../Components/CategoryForm';

export default function Edit() {
    const { props } = usePage();
    const [form] = Form.useForm();
    const category = props.category || {};
    const parentCategories = props.parentCategories || [];

    // Set form initial values
    Form.useEffect(() => {
        form.setFieldsValue(category);
    }, [category]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            router.put(`/categories/${category.id}`, values);
        } catch (error) {
            if (error.errorFields) {
                message.error('Mohon isi field yang diperlukan');
            }
        }
    };

    return (
        <AuthenticatedLayout title="Edit Kategori">
            <Head title="Edit Kategori" />
            <CategoryForm form={form} categories={parentCategories} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Update
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
