import { Link, useForm } from '@inertiajs/react';
import { Button, Form, Input } from 'antd';
import GuestLayout from './GuestLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = () => {
        post('/register');
    };

    return (
        <GuestLayout title="Daftar">
            <Form layout="vertical" onFinish={submit}>
                <Form.Item
                    label="Nama"
                    validateStatus={errors.name ? 'error' : ''}
                    help={errors.name}
                >
                    <Input
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        autoComplete="name"
                    />
                </Form.Item>

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
                        autoComplete="new-password"
                    />
                </Form.Item>

                <Form.Item
                    label="Konfirmasi Kata Sandi"
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
                        Daftar
                    </Button>
                </Form.Item>

                <div style={{ textAlign: 'center' }}>
                    <Link href="/login">Sudah punya akun?</Link>
                </div>
            </Form>
        </GuestLayout>
    );
}
