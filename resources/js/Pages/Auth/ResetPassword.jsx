import { useForm } from '@inertiajs/react';
import { Button, Form, Input } from 'antd';
import GuestLayout from './GuestLayout';

export default function ResetPassword({ email, token }) {
    const { data, setData, post, processing, errors } = useForm({
        token: token,
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    const submit = () => {
        post('/reset-password');
    };

    return (
        <GuestLayout title="Reset Password">
            <Form layout="vertical" onFinish={submit}>
                <input type="hidden" name="token" value={data.token} />

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
                    label="Password"
                    validateStatus={errors.password ? 'error' : ''}
                    help={errors.password}
                >
                    <Input.Password
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="new-password"
                    />
                </Form.Item>

                <Form.Item
                    label="Confirm Password"
                    validateStatus={errors.password_confirmation ? 'error' : ''}
                    help={errors.password_confirmation}
                >
                    <Input.Password
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        autoComplete="new-password"
                    />
                </Form.Item>

                <Form.Item>
                    <Button type="primary" htmlType="submit" loading={processing} block>
                        Reset Password
                    </Button>
                </Form.Item>
            </Form>
        </GuestLayout>
    );
}
