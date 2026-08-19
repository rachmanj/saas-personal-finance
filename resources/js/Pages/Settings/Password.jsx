import { Head, useForm } from '@inertiajs/react';
import { App, Button, Card, Form, Input, message } from 'antd';
import { LockOutlined, SaveOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

function PasswordContent() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = () => {
        put('/settings/password', {
            preserveScroll: true,
            onSuccess: () => {
                message.success('Kata sandi berhasil diperbarui.');
                reset();
            },
            onError: () => {
                message.error('Gagal memperbarui kata sandi. Periksa kembali isian Anda.');
            },
        });
    };

    return (
        <AuthenticatedLayout title="Ubah Kata Sandi">
            <Head title="Ubah Kata Sandi" />

            <Card
                title={
                    <span>
                        <LockOutlined style={{ marginRight: 8 }} />
                        Ubah Kata Sandi
                    </span>
                }
                bordered
                style={{ maxWidth: 600 }}
            >
                <Form layout="vertical" onFinish={handleSubmit}>
                    <Form.Item
                        label="Kata Sandi Saat Ini"
                        validateStatus={errors.current_password ? 'error' : ''}
                        help={errors.current_password}
                        rules={[{ required: true, message: 'Masukkan kata sandi saat ini' }]}
                    >
                        <Input.Password
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                            autoComplete="current-password"
                        />
                    </Form.Item>

                    <Form.Item
                        label="Kata Sandi Baru"
                        validateStatus={errors.password ? 'error' : ''}
                        help={errors.password || 'Minimal 8 karakter.'}
                        rules={[{ required: true, min: 8, message: 'Minimal 8 karakter' }]}
                    >
                        <Input.Password
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            autoComplete="new-password"
                        />
                    </Form.Item>

                    <Form.Item
                        label="Konfirmasi Kata Sandi Baru"
                        validateStatus={errors.password_confirmation ? 'error' : ''}
                        help={errors.password_confirmation}
                        rules={[{ required: true, message: 'Konfirmasi kata sandi baru' }]}
                    >
                        <Input.Password
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            autoComplete="new-password"
                        />
                    </Form.Item>

                    <Form.Item>
                        <Button
                            type="primary"
                            htmlType="submit"
                            icon={<SaveOutlined />}
                            loading={processing}
                        >
                            Simpan Kata Sandi
                        </Button>
                    </Form.Item>
                </Form>
            </Card>
        </AuthenticatedLayout>
    );
}

export default function Password(props) {
    return (
        <App>
            <PasswordContent {...props} />
        </App>
    );
}
