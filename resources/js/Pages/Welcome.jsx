import { Link, Head } from '@inertiajs/react';
import { Button, Typography, Space } from 'antd';
import { ArrowRightOutlined } from '@ant-design/icons';
import GuestLayout from './Auth/GuestLayout';

const { Title, Paragraph } = Typography;

export default function Welcome() {
    return (
        <GuestLayout>
            <Head title="Personal Finance Tracker" />
            <div style={{ textAlign: 'center', padding: '60px 20px' }}>
                <Title level={1} style={{ fontSize: 48, marginBottom: 16 }}>
                    Personal Finance Tracker
                </Title>
                <Paragraph style={{ fontSize: 18, color: 'var(--ant-color-text-secondary)', maxWidth: 500, margin: '0 auto 32px' }}>
                    Track your spending, scan receipts, use voice input, and take control of your finances — all from one app.
                </Paragraph>
                <Space size="middle">
                    <Link href="/register">
                        <Button type="primary" size="large" icon={<ArrowRightOutlined />}>
                            Get Started Free
                        </Button>
                    </Link>
                    <Link href="/login">
                        <Button size="large">Sign In</Button>
                    </Link>
                </Space>
            </div>
        </GuestLayout>
    );
}
