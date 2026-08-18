import { useState } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import {
    App,
    Avatar,
    Button,
    Card,
    Col,
    Empty,
    Form,
    Input,
    List,
    Modal,
    Popconfirm,
    Row,
    Select,
    Space,
    Tag,
    Typography,
    theme,
} from 'antd';
import {
    CrownOutlined,
    DeleteOutlined,
    PlusOutlined,
    SettingOutlined,
    SwapOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

const { Title, Text, Paragraph } = Typography;

export default function Index() {
    const { props } = usePage();
    const { token } = theme.useToken();
    const teams = props.teams || [];
    const currentTeamId = props.current_team_id;

    const [manageTeam, setManageTeam] = useState(null);
    const createForm = useForm({ name: '' });
    const inviteForm = useForm({ email: '', role: 'member' });

    const createTeam = () => createForm.post('/teams', { onSuccess: () => createForm.reset() });

    const switchTeam = (team) => {
        if (team.id === currentTeamId) return;
        router.post(`/teams/${team.id}/switch`);
    };

    const inviteMember = () => {
        inviteForm.post(`/teams/${manageTeam.id}/invite`, {
            onSuccess: () => inviteForm.reset(),
        });
    };

    const removeMember = (team, member) => {
        router.delete(`/teams/${team.id}/members/${member.id}`);
    };

    const deleteTeam = (team) => {
        router.delete(`/teams/${team.id}`);
    };

    const currentUser = props.auth?.user;

    return (
        <App>
            <AuthenticatedLayout title="Tim">
                <Head title="Tim" />

                <Card
                    title="Buat Tim Baru"
                    style={{ marginBottom: 24, background: token.colorBgContainer }}
                >
                    <Form layout="inline" onFinish={createTeam}>
                        <Form.Item
                            validateStatus={createForm.errors.name ? 'error' : ''}
                            help={createForm.errors.name}
                            style={{ flex: 1, minWidth: 260, marginBottom: 0 }}
                        >
                            <Input
                                placeholder="Nama tim (mis. Keluarga, Bisnis)"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                prefix={<TeamOutlined />}
                            />
                        </Form.Item>
                        <Form.Item style={{ marginBottom: 0 }}>
                            <Button
                                type="primary"
                                htmlType="submit"
                                icon={<PlusOutlined />}
                                loading={createForm.processing}
                            >
                                Buat Tim
                            </Button>
                        </Form.Item>
                    </Form>
                </Card>

                {teams.length === 0 ? (
                    <Empty description="Belum ada tim" />
                ) : (
                    <Row gutter={[16, 16]}>
                        {teams.map((team) => {
                            const isCurrent = team.id === currentTeamId;
                            const isOwner = team.role === 'owner';

                            return (
                                <Col xs={24} md={12} lg={8} key={team.id}>
                                    <Card
                                        style={{
                                            height: '100%',
                                            border: isCurrent
                                                ? `2px solid ${token.colorPrimary}`
                                                : `1px solid ${token.colorBorderSecondary}`,
                                            background: token.colorBgContainer,
                                        }}
                                        title={
                                            <Space direction="vertical" size={0}>
                                                <Space>
                                                    <Avatar
                                                        icon={team.personal_team ? <UserOutlined /> : <TeamOutlined />}
                                                        style={{
                                                            background: team.personal_team
                                                                ? token.colorPrimaryBg
                                                                : token.colorInfoBg,
                                                            color: token.colorPrimary,
                                                        }}
                                                    />
                                                    <Text strong>{team.name}</Text>
                                                </Space>
                                            </Space>
                                        }
                                        extra={
                                            <Space size={4} wrap>
                                                <Tag color={team.personal_team ? 'default' : 'geekblue'}>
                                                    {team.personal_team ? 'Pribadi' : 'Bisnis'}
                                                </Tag>
                                                {isCurrent && <Tag color={token.colorPrimary}>Aktif</Tag>}
                                            </Space>
                                        }
                                    >
                                        <Paragraph type="secondary" style={{ marginBottom: 12 }}>
                                            <UserOutlined /> {team.member_count} anggota ·{' '}
                                            {isOwner ? 'Pemilik' : 'Anggota'}
                                        </Paragraph>

                                        <Space wrap>
                                            <Button
                                                icon={<SwapOutlined />}
                                                onClick={() => switchTeam(team)}
                                                disabled={isCurrent}
                                            >
                                                {isCurrent ? 'Tim Aktif' : 'Beralih'}
                                            </Button>
                                            <Button
                                                icon={<SettingOutlined />}
                                                onClick={() => setManageTeam(team)}
                                            >
                                                Kelola
                                            </Button>
                                        </Space>
                                    </Card>
                                </Col>
                            );
                        })}
                    </Row>
                )}

                <Modal
                    title={
                        <Space>
                            <TeamOutlined />
                            Kelola: {manageTeam?.name}
                        </Space>
                    }
                    open={!!manageTeam}
                    onCancel={() => setManageTeam(null)}
                    footer={null}
                    width={640}
                >
                    {manageTeam && (
                        <Space direction="vertical" size={16} style={{ width: '100%' }}>
                            {manageTeam.role === 'owner' && (
                                <>
                                    <Card size="small" title="Undang Anggota">
                                        <Form layout="vertical" onFinish={inviteMember}>
                                            <Form.Item
                                                label="Email"
                                                validateStatus={inviteForm.errors.email ? 'error' : ''}
                                                help={inviteForm.errors.email}
                                            >
                                                <Input
                                                    type="email"
                                                    placeholder="email@contoh.com"
                                                    value={inviteForm.data.email}
                                                    onChange={(e) => inviteForm.setData('email', e.target.value)}
                                                    prefix={<UserOutlined />}
                                                />
                                            </Form.Item>
                                            <Form.Item label="Peran">
                                                <Select
                                                    value={inviteForm.data.role}
                                                    onChange={(value) => inviteForm.setData('role', value)}
                                                    options={[
                                                        { value: 'member', label: 'Anggota' },
                                                        { value: 'owner', label: 'Pemilik' },
                                                    ]}
                                                />
                                            </Form.Item>
                                            <Button
                                                type="primary"
                                                htmlType="submit"
                                                loading={inviteForm.processing}
                                            >
                                                Undang / Tambah
                                            </Button>
                                        </Form>
                                    </Card>

                                    <Card
                                        size="small"
                                        title={`Anggota (${manageTeam.members?.length ?? 0})`}
                                    >
                                        <List
                                            dataSource={manageTeam.members || []}
                                            locale={{ emptyText: 'Tidak ada anggota' }}
                                            renderItem={(member) => (
                                                <List.Item
                                                    actions={[
                                                        manageTeam.role === 'owner' &&
                                                        member.id !== currentUser?.id ? (
                                                            <Popconfirm
                                                                key="remove"
                                                                title="Hapus anggota?"
                                                                description={`Yakin ingin menghapus ${member.name}?`}
                                                                okText="Hapus"
                                                                cancelText="Batal"
                                                                okType="danger"
                                                                onConfirm={() => removeMember(manageTeam, member)}
                                                            >
                                                                <Button
                                                                    size="small"
                                                                    danger
                                                                    icon={<DeleteOutlined />}
                                                                />
                                                            </Popconfirm>
                                                        ) : null,
                                                    ]}
                                                >
                                                    <List.Item.Meta
                                                        avatar={
                                                            <Avatar icon={<UserOutlined />}>
                                                                {member.role === 'owner' ? <CrownOutlined /> : null}
                                                            </Avatar>
                                                        }
                                                        title={
                                                            <Space>
                                                                {member.name}
                                                                {member.role === 'owner' && (
                                                                    <Tag color={token.colorPrimary}>Pemilik</Tag>
                                                                )}
                                                            </Space>
                                                        }
                                                        description={member.email}
                                                    />
                                                </List.Item>
                                            )}
                                        />
                                    </Card>

                                    {!manageTeam.personal_team && (
                                        <Popconfirm
                                            title="Hapus tim?"
                                            description="Semua data tim ini akan dihapus permanen."
                                            okText="Hapus"
                                            cancelText="Batal"
                                            okType="danger"
                                            onConfirm={() => deleteTeam(manageTeam)}
                                        >
                                            <Button danger block icon={<DeleteOutlined />}>
                                                Hapus Tim Ini
                                            </Button>
                                        </Popconfirm>
                                    )}
                                </>
                            )}

                            {manageTeam.role !== 'owner' && (
                                <Text type="secondary">
                                    Hanya pemilik tim yang dapat mengelola anggota dan menghapus tim.
                                </Text>
                            )}
                        </Space>
                    )}
                </Modal>
            </AuthenticatedLayout>
        </App>
    );
}
