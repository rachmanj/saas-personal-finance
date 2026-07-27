import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button, Form, message, Spin } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import AccountForm from '../../Components/AccountForm';
import { apiGet, apiPut } from '../../utils/api';

/**
 * @param {{ id: number|string }} props
 */
export default function Edit({ id }) {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const loadAccount = async () => {
            try {
                const response = await apiGet(`/api/accounts/${id}`);
                form.setFieldsValue(response.data);
            } catch {
                message.error('Failed to load account');
            } finally {
                setLoading(false);
            }
        };

        loadAccount();
    }, [id, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            await apiPut(`/api/accounts/${id}`, values);
            message.success('Account updated');
            router.visit('/accounts');
        } catch (error) {
            if (error.errors) {
                message.error('Validation failed');
            }
        }
    };

    if (loading) {
        return (
            <AuthenticatedLayout title="Edit Account">
                <Spin />
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout title="Edit Account">
            <Head title="Edit Account" />
            <AccountForm form={form} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Update Account
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
