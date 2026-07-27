import { Link, usePage } from '@inertiajs/react';
import { Layout, Menu } from 'antd';
import {
    DashboardOutlined,
    WalletOutlined,
    TagsOutlined,
    AppstoreOutlined,
    TransactionOutlined,
} from '@ant-design/icons';

const { Header, Sider, Content } = Layout;

const menuItems = [
    { key: '/dashboard', icon: <DashboardOutlined />, label: <Link href="/dashboard">Dashboard</Link> },
    { key: '/transactions', icon: <TransactionOutlined />, label: <Link href="/transactions">Transactions</Link> },
    { key: '/accounts', icon: <WalletOutlined />, label: <Link href="/accounts">Accounts</Link> },
    { key: '/categories', icon: <AppstoreOutlined />, label: <Link href="/categories">Categories</Link> },
    { key: '/tags', icon: <TagsOutlined />, label: <Link href="/tags">Tags</Link> },
];

/**
 * @param {{ children: import('react').ReactNode, title?: string }} props
 */
export default function AuthenticatedLayout({ children, title }) {
    const { url } = usePage();

    const selectedKey = menuItems.find((item) => url.startsWith(item.key))?.key || '/dashboard';

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider breakpoint="lg" collapsedWidth={0}>
                <div style={{ padding: '16px', color: '#fff', fontWeight: 600, fontSize: 16 }}>
                    Finance Tracker
                </div>
                <Menu theme="dark" mode="inline" selectedKeys={[selectedKey]} items={menuItems} />
            </Sider>
            <Layout>
                <Header style={{ padding: '0 24px', display: 'flex', alignItems: 'center' }}>
                    <h2 style={{ margin: 0, color: 'inherit' }}>{title}</h2>
                </Header>
                <Content style={{ margin: 24, padding: 24, background: 'var(--ant-color-bg-container)', borderRadius: 8 }}>
                    {children}
                </Content>
            </Layout>
        </Layout>
    );
}
