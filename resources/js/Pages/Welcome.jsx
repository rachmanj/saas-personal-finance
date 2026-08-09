import { Link, Head } from '@inertiajs/react';
import { Button, Typography, Space } from 'antd';
import { ArrowRightOutlined } from '@ant-design/icons';
import GuestLayout from './Auth/GuestLayout';

const { Title, Paragraph } = Typography;

export default function Welcome() {
    return (
        <GuestLayout>
            <Head title="KeuanganKu - Pelacak Keuangan Pribadi" />
            <div style={{ textAlign: 'center', padding: '60px 20px' }}>
                <Title level={1} style={{ fontSize: 48, marginBottom: 16 }}>
                    KeuanganKu
                </Title>
                <Paragraph style={{ fontSize: 18, color: 'var(--ant-color-text-secondary)', maxWidth: 500, margin: '0 auto 32px' }}>
                    Lacak pengeluaran, pindai struk, gunakan input suara, dan kendalikan keuangan Anda — semua dalam satu aplikasi.
                </Paragraph>
                <Space size="middle">
                    <Link href="/register">
                        <Button type="primary" size="large" icon={<ArrowRightOutlined />}>
                            Mulai Gratis
                        </Button>
                    </Link>
                    <Link href="/login">
                        <Button size="large">Masuk</Button>
                    </Link>
                </Space>
            </div>
        </GuestLayout>
    );
}
