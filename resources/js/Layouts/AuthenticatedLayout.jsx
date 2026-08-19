import { Link, usePage, router } from '@inertiajs/react';
import { Layout, Menu, Dropdown, Button, Space, theme } from 'antd';
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
    TeamOutlined,
    CheckOutlined,
    LockOutlined,
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
    { key: '/teams', icon: <TeamOutlined />, label: <Link href="/teams">Tim</Link> },
    { key: '/settings', icon: <SettingOutlined />, label: <Link href="/settings/billing">Pengaturan</Link> },
    { key: '/settings/currency', icon: <DollarOutlined />, label: <Link href="/settings/currency">Mata Uang</Link> },
    { key: '/settings/password', icon: <LockOutlined />, label: <Link href="/settings/password">Kata Sandi</Link> },
    { key: '/settings/telegram', icon: <SendOutlined />, label: <Link href="/settings/telegram">Telegram</Link> },
];

export default function AuthenticatedLayout({ children, title }) {
    const { url, props } = usePage();
    const { token } = theme.useToken();
    const user = props.auth?.user;
    const currentTeam = props.current_team;
    const teams = props.teams || [];

    const selectedKey = menuItems.find((item) => url.startsWith(item.key))?.key || '/dashboard';

    const handleLogout = () => {
        router.post('/logout');
    };

    const teamMenuItems = [
        ...(teams || []).map((team) => ({
            key: `team-${team.id}`,
            icon: team.id === currentTeam?.id ? <CheckOutlined /> : <TeamOutlined />,
            label: team.name,
            onClick: () => router.post(`/teams/${team.id}/switch`),
        })),
        { type: 'divider' },
        {
            key: 'manage-teams',
            icon: <SettingOutlined />,
            label: 'Kelola Tim',
            onClick: () => router.visit('/teams'),
        },
    ];

    const userMenuItems = [
        { key: 'email', label: user?.email || 'User', disabled: true },
        { type: 'divider' },
        { key: 'logout', icon: <LogoutOutlined />, label: 'Keluar', onClick: handleLogout },
    ];

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider breakpoint="lg" collapsedWidth={0} style={{ background: 'var(--m3-surface-container)' }}>
                <div style={{ padding: '16px', color: token.colorPrimary, fontWeight: 700, fontSize: 18 }}>
                    KeuanganKu
                </div>
                <Menu theme="dark" mode="inline" selectedKeys={[selectedKey]} items={menuItems} style={{ background: 'transparent' }} />
            </Sider>
            <Layout>
                <Header style={{ padding: '0 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: 'var(--m3-surface)' }}>
                    <h2 style={{ margin: 0, color: token.colorText }}>{title}</h2>
                    <Space>
                        <Dropdown menu={{ items: teamMenuItems }} placement="bottomRight">
                            <Button type="text" style={{ color: token.colorText }}>
                                <Space>
                                    <TeamOutlined />
                                    {currentTeam?.name || 'Tim'}
                                </Space>
                            </Button>
                        </Dropdown>
                        <Dropdown menu={{ items: userMenuItems }} placement="bottomRight">
                            <Button type="text" style={{ color: token.colorText }}>
                                <Space>
                                    <UserOutlined />
                                    {user?.name || 'User'}
                                </Space>
                            </Button>
                        </Dropdown>
                    </Space>
                </Header>
                <Content
                    id="main-content"
                    role="main"
                    style={{ margin: 24, padding: 24, background: token.colorBgContainer, borderRadius: token.borderRadiusLG }}
                >
                    {children}
                </Content>
            </Layout>
        </Layout>
    );
}
