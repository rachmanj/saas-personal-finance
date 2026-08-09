import { Link, useForm } from '@inertiajs/react';
import { Alert, Button, Checkbox, Form, Input } from 'antd';
import GuestLayout from './GuestLayout';

export default function Login({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = () => {
        post('/login');
    };

    return (
        <GuestLayout title="Masuk">
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

                <Form.Item
                    label="Kata Sandi"
                    validateStatus={errors.password ? 'error' : ''}
                    help={errors.password}
                >
                    <Input.Password
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="current-password"
                    />
                </Form.Item>

                <Form.Item>
                    <Checkbox
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                    >
                        Ingat saya
                    </Checkbox>
                </Form.Item>

                <Form.Item>
                    <Button type="primary" htmlType="submit" loading={processing} block>
                        Masuk
                    </Button>
                </Form.Item>

                <div style={{ textAlign: 'center' }}>
                    <Link href="/forgot-password">Lupa kata sandi?</Link>
                </div>
            </Form>
        </GuestLayout>
    );
}
