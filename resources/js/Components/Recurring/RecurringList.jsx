import { useEffect, useState } from 'react';
import { Table, Button, Space, Modal, Tag, App } from 'antd';
import {
    EditOutlined,
    DeleteOutlined,
    PlusOutlined,
    StepForwardOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons';
import { apiDelete, apiGet, apiPost } from '../../utils/api';
import dayjs from 'dayjs';

const TYPE_COLORS = {
    income: 'green',
    expense: 'red',
    transfer: 'blue',
};

/**
 * @param {{
 *   recurring?: object[],
 *   onEdit: (item: object) => void,
 *   onDelete?: (item: object) => void,
 *   onSkip?: (item: object) => void,
 *   onPostNow?: (item: object) => void,
 *   onAdd?: () => void,
 *   refreshKey?: number,
 * }} props
 */
export default function RecurringList({
    recurring: recurringProp,
    onEdit,
    onDelete,
    onSkip,
    onPostNow,
    onAdd,
    refreshKey = 0,
}) {
    const { message } = App.useApp();
    const [recurring, setRecurring] = useState(recurringProp || []);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [actionLoading, setActionLoading] = useState(null);

    const loadRecurring = async () => {
        setLoading(true);
        setError(null);
        try {
            const res = await apiGet('/api/recurring-transactions');
            setRecurring(res.data || []);
        } catch (err) {
            setError(err.message || 'Failed to load recurring transactions');
            message.error('Failed to load recurring transactions');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadRecurring();
    }, [refreshKey]);

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Delete recurring transaction?',
            content: `Are you sure you want to delete "${record.description || 'this item'}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/recurring-transactions/${record.id}`);
                    message.success('Recurring transaction deleted');
                    onDelete?.(record);
                    loadRecurring();
                } catch (err) {
                    message.error(err.message || 'Failed to delete');
                }
            },
        });
    };

    const handleSkip = async (record) => {
        setActionLoading(`skip-${record.id}`);
        try {
            await apiPost(`/api/recurring-transactions/${record.id}/skip`, {});
            message.success('Skipped to next due date');
            onSkip?.(record);
            loadRecurring();
        } catch (err) {
            message.error(err.message || 'Failed to skip');
        } finally {
            setActionLoading(null);
        }
    };

    const handlePostNow = async (record) => {
        setActionLoading(`post-${record.id}`);
        try {
            await apiPost(`/api/recurring-transactions/${record.id}/post-now`, {});
            message.success('Transaction posted');
            onPostNow?.(record);
            loadRecurring();
        } catch (err) {
            message.error(err.message || 'Failed to post transaction');
        } finally {
            setActionLoading(null);
        }
    };

    const columns = [
        {
            title: 'Description',
            dataIndex: 'description',
            key: 'description',
            render: (text) => text || '—',
        },
        {
            title: 'Type',
            dataIndex: 'type',
            key: 'type',
            render: (type) => <Tag color={TYPE_COLORS[type] || 'default'}>{type}</Tag>,
        },
        {
            title: 'Amount',
            key: 'amount',
            render: (_, record) => `${record.currency} ${Number(record.amount).toFixed(2)}`,
        },
        {
            title: 'Frequency',
            dataIndex: 'frequency',
            key: 'frequency',
            render: (freq, record) => (
                <Tag>
                    {record.interval > 1 ? `Every ${record.interval} ` : ''}
                    {freq}
                </Tag>
            ),
        },
        {
            title: 'Account',
            dataIndex: ['account', 'name'],
            key: 'account',
            render: (_, record) => record.account?.name || '—',
        },
        {
            title: 'Next Due Date',
            dataIndex: 'next_due_date',
            key: 'next_due_date',
            render: (date) => (date ? dayjs(date).format('MMM D, YYYY') : '—'),
        },
        {
            title: 'Status',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (active) => (
                <Tag color={active ? 'green' : 'default'}>{active ? 'Active' : 'Inactive'}</Tag>
            ),
        },
        {
            title: 'Actions',
            key: 'actions',
            render: (_, record) => (
                <Space wrap>
                    <Button
                        size="small"
                        icon={<StepForwardOutlined />}
                        loading={actionLoading === `skip-${record.id}`}
                        onClick={() => handleSkip(record)}
                        disabled={!record.is_active}
                    >
                        Skip
                    </Button>
                    <Button
                        size="small"
                        icon={<ThunderboltOutlined />}
                        loading={actionLoading === `post-${record.id}`}
                        onClick={() => handlePostNow(record)}
                        disabled={!record.is_active}
                    >
                        Post Now
                    </Button>
                    <Button icon={<EditOutlined />} size="small" onClick={() => onEdit(record)} />
                    <Button icon={<DeleteOutlined />} size="small" danger onClick={() => handleDelete(record)} />
                </Space>
            ),
        },
    ];

    return (
        <Table
            columns={columns}
            dataSource={recurring}
            rowKey="id"
            loading={loading}
            pagination={{ pageSize: 10 }}
            scroll={{ x: true }}
            locale={{ emptyText: error || 'No recurring transactions yet' }}
            title={onAdd ? () => (
                <Button type="primary" icon={<PlusOutlined />} onClick={onAdd}>
                    Add Recurring
                </Button>
            ) : undefined}
        />
    );
}
