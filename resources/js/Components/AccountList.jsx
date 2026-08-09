import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-table';
import { Button, Modal, message, Tag, Space } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import { apiDelete, apiGet } from '../utils/api';
import AccountForm from './AccountForm';
import { Form } from 'antd';
import { apiPost } from '../utils/api';

/**
 * @param {{ showCreateModal?: boolean, onCreateModalClose?: () => void, accounts?: object[] }} props
 */
export default function AccountList({ showCreateModal = false, onCreateModalClose, accounts: accountsProp }) {
    const [accounts, setAccounts] = useState(accountsProp || []);
    const [loading, setLoading] = useState(!accountsProp);
    const [createOpen, setCreateOpen] = useState(showCreateModal);
    const [createForm] = Form.useForm();

    const loadAccounts = async () => {
        setLoading(true);
        try {
            const response = await apiGet('/api/accounts');
            setAccounts(response.data || []);
        } catch {
            message.error('Gagal memuat akun');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (!accountsProp) loadAccounts();
    }, []);

    useEffect(() => {
        setCreateOpen(showCreateModal);
    }, [showCreateModal]);

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Hapus akun?',
            content: `Yakin ingin menghapus "${record.name}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/accounts/${record.id}`);
                    message.success('Akun dihapus');
                    loadAccounts();
                } catch {
                    message.error('Gagal menghapus akun');
                }
            },
        });
    };

    const handleCreate = async () => {
        try {
            const values = await createForm.validateFields();
            await apiPost('/api/accounts', values);
            message.success('Akun dibuat');
            createForm.resetFields();
            setCreateOpen(false);
            onCreateModalClose?.();
            loadAccounts();
        } catch (error) {
            if (error.errors) {
                message.error('Validasi gagal');
            }
        }
    };

    const columns = [
        { title: 'Nama', dataIndex: 'name', key: 'name' },
        { title: 'Tipe', dataIndex: 'type', key: 'type', render: (type) => type?.replace('_', ' ') },
        { title: 'Mata Uang', dataIndex: 'currency', key: 'currency' },
        {
            title: 'Saldo',
            dataIndex: 'balance',
            key: 'balance',
            render: (balance, record) => `${record.currency} ${Number(balance).toFixed(2)}`,
        },
        {
            title: 'Aktif',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (active) => <Tag color={active ? 'green' : 'default'}>{active ? 'Ya' : 'Tidak'}</Tag>,
        },
        {
            title: 'Aksi',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Link href={`/accounts/${record.id}/edit`}>
                        <Button icon={<EditOutlined />} size="small" />
                    </Link>
                    <Button icon={<DeleteOutlined />} size="small" danger onClick={() => handleDelete(record)} />
                </Space>
            ),
        },
    ];

    return (
        <>
            <ProTable
                columns={columns}
                dataSource={accounts}
                rowKey="id"
                loading={loading}
                search={false}
                options={false}
                pagination={{ pageSize: 10 }}
                toolBarRender={() => [
                    <Button key="create" type="primary" icon={<PlusOutlined />} onClick={() => setCreateOpen(true)}>
                        Buat Akun
                    </Button>,
                ]}
            />

            <Modal
                title="Buat Akun"
                open={createOpen}
                onOk={handleCreate}
                onCancel={() => {
                    setCreateOpen(false);
                    onCreateModalClose?.();
                }}
                destroyOnClose
            >
                <AccountForm form={createForm} />
            </Modal>
        </>
    );
}
