import { Head, router } from '@inertiajs/react';
import { Button, Form, message } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import AccountForm from '../../Components/AccountForm';
import { apiPost } from '../../utils/api';

export default function Create() {
    const [form] = Form.useForm();

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            await apiPost('/api/accounts', values);
            message.success('Account created');
            router.visit('/accounts');
        } catch (error) {
            if (error.errors) {
                message.error('Validation failed');
            }
        }
    };

    return (
        <AuthenticatedLayout title="Create Account">
            <Head title="Create Account" />
            <AccountForm form={form} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Create Account
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
