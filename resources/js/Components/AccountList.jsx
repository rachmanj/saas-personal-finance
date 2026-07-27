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
 * @param {{ showCreateModal?: boolean, onCreateModalClose?: () => void }} props
 */
export default function AccountList({ showCreateModal = false, onCreateModalClose }) {
    const [accounts, setAccounts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [createOpen, setCreateOpen] = useState(showCreateModal);
    const [createForm] = Form.useForm();

    const loadAccounts = async () => {
        setLoading(true);
        try {
            const response = await apiGet('/api/accounts');
            setAccounts(response.data || []);
        } catch {
            message.error('Failed to load accounts');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadAccounts();
    }, []);

    useEffect(() => {
        setCreateOpen(showCreateModal);
    }, [showCreateModal]);

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Delete account?',
            content: `Are you sure you want to delete "${record.name}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/accounts/${record.id}`);
                    message.success('Account deleted');
                    loadAccounts();
                } catch {
                    message.error('Failed to delete account');
                }
            },
        });
    };

    const handleCreate = async () => {
        try {
            const values = await createForm.validateFields();
            await apiPost('/api/accounts', values);
            message.success('Account created');
            createForm.resetFields();
            setCreateOpen(false);
            onCreateModalClose?.();
            loadAccounts();
        } catch (error) {
            if (error.errors) {
                message.error('Validation failed');
            }
        }
    };

    const columns = [
        { title: 'Name', dataIndex: 'name', key: 'name' },
        { title: 'Type', dataIndex: 'type', key: 'type', render: (type) => type?.replace('_', ' ') },
        { title: 'Currency', dataIndex: 'currency', key: 'currency' },
        {
            title: 'Balance',
            dataIndex: 'balance',
            key: 'balance',
            render: (balance, record) => `${record.currency} ${Number(balance).toFixed(2)}`,
        },
        {
            title: 'Active',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (active) => <Tag color={active ? 'green' : 'default'}>{active ? 'Yes' : 'No'}</Tag>,
        },
        {
            title: 'Actions',
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
                        Create Account
                    </Button>,
                ]}
            />

            <Modal
                title="Create Account"
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
