import { Card } from 'antd';

export default function GuestLayout({ children, title }) {
    return (
        <div
            style={{
                minHeight: '100vh',
                background: 'var(--m3-surface, #1c1b1f)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: 24,
            }}
        >
            <Card title={title} style={{ width: '100%', maxWidth: 400, borderRadius: 20 }}>
                {children}
            </Card>
        </div>
    );
}
