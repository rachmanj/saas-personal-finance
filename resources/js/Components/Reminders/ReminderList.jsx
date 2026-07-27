import { useEffect, useState } from 'react';
import { Table, Button, Space, Modal, Tag, App } from 'antd';
import {
    EditOutlined,
    DeleteOutlined,
    PlusOutlined,
    CheckOutlined,
} from '@ant-design/icons';
import { apiDelete, apiGet, apiPut } from '../../utils/api';
import dayjs from 'dayjs';

/**
 * @param {{
 *   onEdit: (item: object) => void,
 *   onAdd?: () => void,
 *   refreshKey?: number,
 * }} props
 */
export default function ReminderList({ onEdit, onAdd, refreshKey = 0 }) {
    const { message } = App.useApp();
    const [reminders, setReminders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(null);

    const loadReminders = async () => {
        setLoading(true);
        try {
            const res = await apiGet('/api/bill-reminders');
            setReminders(res.data || []);
        } catch (err) {
            message.error(err.message || 'Failed to load reminders');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadReminders();
    }, [refreshKey]);

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Delete bill reminder?',
            content: `Are you sure you want to delete "${record.name}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/bill-reminders/${record.id}`);
                    message.success('Reminder deleted');
                    loadReminders();
                } catch (err) {
                    message.error(err.message || 'Failed to delete');
                }
            },
        });
    };

    const handleTogglePaid = async (record) => {
        if (record.is_paid) {
            return;
        }

        setActionLoading(`paid-${record.id}`);
        try {
            await apiPut(`/api/bill-reminders/${record.id}/paid`, {});
            message.success('Marked as paid');
            loadReminders();
        } catch (err) {
            message.error(err.message || 'Failed to update');
        } finally {
            setActionLoading(null);
        }
    };

    const columns = [
        {
            title: 'Name',
            dataIndex: 'name',
            key: 'name',
        },
        {
            title: 'Amount',
            key: 'amount',
            render: (_, record) => `${record.currency} ${Number(record.amount).toFixed(2)}`,
        },
        {
            title: 'Due Date',
            dataIndex: 'due_date',
            key: 'due_date',
            render: (date) => (date ? dayjs(date).format('MMM D, YYYY') : '—'),
        },
        {
            title: 'Status',
            dataIndex: 'is_paid',
            key: 'is_paid',
            render: (paid) => (
                <Tag color={paid ? 'green' : 'orange'}>{paid ? 'Paid' : 'Unpaid'}</Tag>
            ),
        },
        {
            title: 'Actions',
            key: 'actions',
            render: (_, record) => (
                <Space wrap>
                    {!record.is_paid && (
                        <Button
                            size="small"
                            icon={<CheckOutlined />}
                            loading={actionLoading === `paid-${record.id}`}
                            onClick={() => handleTogglePaid(record)}
                        >
                            Mark Paid
                        </Button>
                    )}
                    <Button icon={<EditOutlined />} size="small" onClick={() => onEdit(record)} />
                    <Button icon={<DeleteOutlined />} size="small" danger onClick={() => handleDelete(record)} />
                </Space>
            ),
        },
    ];

    return (
        <Table
            columns={columns}
            dataSource={reminders}
            rowKey="id"
            loading={loading}
            pagination={{ pageSize: 10 }}
            scroll={{ x: true }}
            locale={{ emptyText: 'No bill reminders yet' }}
            title={onAdd ? () => (
                <Button type="primary" icon={<PlusOutlined />} onClick={onAdd}>
                    Add Reminder
                </Button>
            ) : undefined}
        />
    );
}
