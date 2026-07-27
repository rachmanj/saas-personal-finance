import { Link, useForm } from '@inertiajs/react';
import { Alert, Button, Form, Input } from 'antd';
import GuestLayout from './GuestLayout';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = () => {
        post('/forgot-password');
    };

    return (
        <GuestLayout title="Forgot Password">
            {status && (
                <Alert
                    message={status}
                    type="success"
                    showIcon
                    style={{ marginBottom: 16 }}
                />
            )}

            <Form layout="vertical" onFinish={submit}>
                <Form.Item
                    label="Email"
                    validateStatus={errors.email ? 'error' : ''}
                    help={errors.email}
                >
                    <Input
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="username"
                    />
                </Form.Item>

                <Form.Item>
                    <Button type="primary" htmlType="submit" loading={processing} block>
                        Email Password Reset Link
                    </Button>
                </Form.Item>

                <div style={{ textAlign: 'center' }}>
                    <Link href="/login">Back to login</Link>
                </div>
            </Form>
        </GuestLayout>
    );
}
