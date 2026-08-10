import { Head, usePage, router } from '@inertiajs/react';
import { useEffect } from 'react';
import { Button, Form, message } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import AccountForm from '../../Components/AccountForm';

export default function Edit() {
    const { props } = usePage();
    const [form] = Form.useForm();
    const account = props.account || {};

    useEffect(() => {
        if (account.id) {
            form.setFieldsValue(account);
        }
    }, [account]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            router.put(`/accounts/${account.id}`, values);
        } catch (error) {
            if (error.errorFields) {
                message.error('Mohon isi field yang diperlukan');
            }
        }
    };

    return (
        <AuthenticatedLayout title="Edit Akun">
            <Head title="Edit Akun" />
            <AccountForm form={form} />
            <div style={{ marginTop: 16 }}>
                <Button type="primary" onClick={handleSubmit}>
                    Update
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
