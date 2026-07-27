import { useEffect, useState } from 'react';
import { Table, Button, Space, Modal, App } from 'antd';
import { EditOutlined, DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { apiDelete, apiGet } from '../../utils/api';
import BudgetProgressBar from './BudgetProgressBar';

/**
 * @param {{
 *   budgets?: object[],
 *   onEdit: (budget: object) => void,
 *   onDelete?: (budget: object) => void,
 *   onAdd?: () => void,
 *   refreshKey?: number,
 * }} props
 */
export default function BudgetList({ budgets: budgetsProp, onEdit, onDelete, onAdd, refreshKey = 0 }) {
    const { message } = App.useApp();
    const [budgets, setBudgets] = useState(budgetsProp || []);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const loadBudgets = async () => {
        setLoading(true);
        setError(null);
        try {
            const res = await apiGet('/api/budgets');
            setBudgets(res.data || []);
        } catch (err) {
            setError(err.message || 'Failed to load budgets');
            message.error('Failed to load budgets');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadBudgets();
    }, [refreshKey]);

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Delete budget?',
            content: `Are you sure you want to delete the budget for "${record.category?.name || 'Unknown'}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/budgets/${record.id}`);
                    message.success('Budget deleted');
                    onDelete?.(record);
                    loadBudgets();
                } catch (err) {
                    message.error(err.message || 'Failed to delete budget');
                }
            },
        });
    };

    const columns = [
        {
            title: 'Category',
            dataIndex: ['category', 'name'],
            key: 'category',
            render: (_, record) => record.category?.name || '—',
        },
        {
            title: 'Amount',
            key: 'amount',
            render: (_, record) => `${record.currency} ${Number(record.amount).toFixed(2)}`,
        },
        {
            title: 'Period',
            dataIndex: 'period',
            key: 'period',
            render: (period) => period?.charAt(0).toUpperCase() + period?.slice(1),
        },
        {
            title: 'Utilization',
            key: 'utilization',
            render: (_, record) => (
                <BudgetProgressBar
                    percent={record.utilization?.percent ?? 0}
                    status={record.utilization?.status ?? 'ok'}
                />
            ),
        },
        {
            title: 'Actions',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Button icon={<EditOutlined />} size="small" onClick={() => onEdit(record)} />
                    <Button icon={<DeleteOutlined />} size="small" danger onClick={() => handleDelete(record)} />
                </Space>
            ),
        },
    ];

    return (
        <Table
            columns={columns}
            dataSource={budgets}
            rowKey="id"
            loading={loading}
            pagination={{ pageSize: 10 }}
            locale={{ emptyText: error || 'No budgets yet' }}
            title={onAdd ? () => (
                <Button type="primary" icon={<PlusOutlined />} onClick={onAdd}>
                    Add Budget
                </Button>
            ) : undefined}
        />
    );
}
