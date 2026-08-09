import { Link, usePage, router } from '@inertiajs/react';
import { Layout, Menu, Dropdown, Button, Space } from 'antd';
import {
    DashboardOutlined,
    WalletOutlined,
    TagsOutlined,
    AppstoreOutlined,
    TransactionOutlined,
    PieChartOutlined,
    SyncOutlined,
    BellOutlined,
    DownloadOutlined,
    SettingOutlined,
    SendOutlined,
    UserOutlined,
    LogoutOutlined,
    DollarOutlined,
} from '@ant-design/icons';

const { Header, Sider, Content } = Layout;

const menuItems = [
    { key: '/dashboard', icon: <DashboardOutlined />, label: <Link href="/dashboard">Dasbor</Link> },
    { key: '/transactions', icon: <TransactionOutlined />, label: <Link href="/transactions">Transaksi</Link> },
    { key: '/budgets', icon: <PieChartOutlined />, label: <Link href="/budgets">Anggaran</Link> },
    { key: '/recurring-transactions', icon: <SyncOutlined />, label: <Link href="/recurring-transactions">Berulang</Link> },
    { key: '/reminders', icon: <BellOutlined />, label: <Link href="/reminders">Pengingat</Link> },
    { key: '/reports', icon: <DownloadOutlined />, label: <Link href="/reports">Laporan</Link> },
    { key: '/accounts', icon: <WalletOutlined />, label: <Link href="/accounts">Akun</Link> },
    { key: '/categories', icon: <AppstoreOutlined />, label: <Link href="/categories">Kategori</Link> },
    { key: '/tags', icon: <TagsOutlined />, label: <Link href="/tags">Tag</Link> },
    { key: '/settings', icon: <SettingOutlined />, label: <Link href="/settings/billing">Pengaturan</Link> },
    { key: '/settings/currency', icon: <DollarOutlined />, label: <Link href="/settings/currency">Mata Uang</Link> },
    { key: '/settings/telegram', icon: <SendOutlined />, label: <Link href="/settings/telegram">Telegram</Link> },
];

export default function AuthenticatedLayout({ children, title }) {
    const { url, props } = usePage();
    const user = props.auth?.user;

    const selectedKey = menuItems.find((item) => url.startsWith(item.key))?.key || '/dashboard';

    const handleLogout = () => {
        router.post('/logout');
    };

    const userMenuItems = [
        { key: 'email', label: user?.email || 'User', disabled: true },
        { type: 'divider' },
        { key: 'logout', icon: <LogoutOutlined />, label: 'Keluar', onClick: handleLogout },
    ];

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider breakpoint="lg" collapsedWidth={0}>
                <div style={{ padding: '16px', color: '#fff', fontWeight: 600, fontSize: 16 }}>
                    KeuanganKu
                </div>
                <Menu theme="dark" mode="inline" selectedKeys={[selectedKey]} items={menuItems} />
            </Sider>
            <Layout>
                <Header style={{ padding: '0 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <h2 style={{ margin: 0, color: 'inherit' }}>{title}</h2>
                    <Dropdown menu={{ items: userMenuItems }} placement="bottomRight">
                        <Button type="text" style={{ color: '#fff' }}>
                            <Space>
                                <UserOutlined />
                                {user?.name || 'User'}
                            </Space>
                        </Button>
                    </Dropdown>
                </Header>
                <Content
                    id="main-content"
                    role="main"
                    style={{ margin: 24, padding: 24, background: 'var(--ant-color-bg-container)', borderRadius: 8 }}
                >
                    {children}
                </Content>
            </Layout>
        </Layout>
    );
}
