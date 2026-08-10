import { Head, router } from '@inertiajs/react';
import { Button, Form, message } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import AccountForm from '../../Components/AccountForm';

export default function Create() {
    const [form] = Form.useForm();

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            router.post('/accounts', values);
        } catch (error) {
            if (error.errorFields) {
                message.error('Mohon isi field yang diperlukan');
            }
        }
    };

    return (
        <AuthenticatedLayout title="Buat Akun">
            <Head title="Buat Akun" />
            <AccountForm form={form} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Simpan
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
