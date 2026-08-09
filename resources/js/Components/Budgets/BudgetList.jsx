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
    const [loading, setLoading] = useState(!budgetsProp);
    const [error, setError] = useState(null);

    const loadBudgets = async () => {
        setLoading(true);
        setError(null);
        try {
            const res = await apiGet('/api/budgets');
            setBudgets(res.data || []);
        } catch (err) {
            setError(err.message || 'Gagal memuat anggaran');
            message.error('Gagal memuat anggaran');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (budgetsProp) {
            setBudgets(budgetsProp);
            setLoading(false);
        } else {
            loadBudgets();
        }
    }, [refreshKey]);

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Hapus anggaran?',
            content: `Are you sure you want to delete the budget for "${record.category?.name || 'Unknown'}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/budgets/${record.id}`);
                    message.success('Anggaran dihapus');
                    onDelete?.(record);
                    loadBudgets();
                } catch (err) {
                    message.error(err.message || 'Gagal menghapus anggaran');
                }
            },
        });
    };

    const columns = [
        {
            title: 'Kategori',
            dataIndex: ['category', 'name'],
            key: 'category',
            render: (_, record) => record.category?.name || '—',
        },
        {
            title: 'Jumlah',
            key: 'amount',
            render: (_, record) => `${record.currency} ${Number(record.amount).toFixed(2)}`,
        },
        {
            title: 'Periode',
            dataIndex: 'period',
            key: 'period',
            render: (period) => period?.charAt(0).toUpperCase() + period?.slice(1),
        },
        {
            title: 'Pemakaian',
            key: 'utilization',
            render: (_, record) => (
                <BudgetProgressBar
                    percent={record.utilization?.percent ?? 0}
                    status={record.utilization?.status ?? 'ok'}
                />
            ),
        },
        {
            title: 'Aksi',
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
            locale={{ emptyText: error || 'Belum ada anggaran' }}
            title={onAdd ? () => (
                <Button type="primary" icon={<PlusOutlined />} onClick={onAdd}>
                    Tambah Anggaran
                </Button>
            ) : undefined}
        />
    );
}
