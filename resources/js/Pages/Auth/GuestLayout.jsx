import { Card } from 'antd';

export default function GuestLayout({ children, title }) {
    return (
        <div
            style={{
                minHeight: '100vh',
                background: '#141414',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: 24,
            }}
        >
            <Card title={title} style={{ width: '100%', maxWidth: 400 }}>
                {children}
            </Card>
        </div>
    );
}
