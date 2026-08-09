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
} from '@ant-design/icons';

const { Header, Sider, Content } = Layout;

const menuItems = [
    { key: '/dashboard', icon: <DashboardOutlined />, label: <Link href="/dashboard">Dashboard</Link> },
    { key: '/transactions', icon: <TransactionOutlined />, label: <Link href="/transactions">Transactions</Link> },
    { key: '/budgets', icon: <PieChartOutlined />, label: <Link href="/budgets">Budgets</Link> },
    { key: '/recurring-transactions', icon: <SyncOutlined />, label: <Link href="/recurring-transactions">Recurring</Link> },
    { key: '/reminders', icon: <BellOutlined />, label: <Link href="/reminders">Reminders</Link> },
    { key: '/reports', icon: <DownloadOutlined />, label: <Link href="/reports">Reports</Link> },
    { key: '/accounts', icon: <WalletOutlined />, label: <Link href="/accounts">Accounts</Link> },
    { key: '/categories', icon: <AppstoreOutlined />, label: <Link href="/categories">Categories</Link> },
    { key: '/tags', icon: <TagsOutlined />, label: <Link href="/tags">Tags</Link> },
    { key: '/settings', icon: <SettingOutlined />, label: <Link href="/settings/billing">Settings</Link> },
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
        { key: 'logout', icon: <LogoutOutlined />, label: 'Sign Out', onClick: handleLogout },
    ];

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider breakpoint="lg" collapsedWidth={0}>
                <div style={{ padding: '16px', color: '#fff', fontWeight: 600, fontSize: 16 }}>
                    Finance Tracker
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
