import { useState, useCallback, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { App, Table, Button, Space, Tag, Tooltip, Modal } from 'antd';
import {
    PlusOutlined,
    EditOutlined,
    EyeOutlined,
    ArrowUpOutlined,
    ArrowDownOutlined,
    SwapOutlined,
    DeleteOutlined,
} from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { apiDelete } from '../../utils/api';
import dayjs from 'dayjs';

const typeColorMap = { income: 'green', expense: 'red', transfer: 'blue' };

export default function Index() {
    const { props } = usePage();
    const transactions = props.transactions || [];
    const { message } = App.useApp();

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Hapus transaksi?',
            content: 'Yakin hapus transaksi ini?',
            okText: 'Hapus',
            okType: 'danger',
            cancelText: 'Batal',
            onOk: async () => {
                try {
                    await apiDelete(`/api/transactions/${record.id}`);
                    message.success('Transaksi berhasil dihapus');
                    router.reload();
                } catch {
                    message.error('Gagal menghapus transaksi');
                }
            },
        });
    };

    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 50 },
        { title: 'Tanggal', dataIndex: 'transaction_date', key: 'date', width: 100,
            render: (v) => dayjs(v).format('DD/MM/YY') },
        { title: 'Deskripsi', dataIndex: 'description', key: 'desc', ellipsis: true },
        { title: 'Tipe', dataIndex: 'type', key: 'type', width: 90,
            render: (type) => (
                <Tag color={typeColorMap[type]}>
                    {type === 'income' ? <ArrowUpOutlined /> : type === 'expense' ? <ArrowDownOutlined /> : <SwapOutlined />}
                    {' '}{type}
                </Tag>
            )},
        { title: 'Jumlah', dataIndex: 'amount', key: 'amount', width: 140,
            render: (v, r) => (
                <span style={{ color: r.type === 'income' ? '#52c41a' : r.type === 'expense' ? '#ff4d4f' : '#1677ff', fontWeight: 600 }}>
                    {r.type === 'income' ? '+' : r.type === 'expense' ? '-' : ''}Rp {Number(v).toLocaleString('id-ID')}
                </span>
            )},
        { title: 'Kategori', dataIndex: 'category', key: 'category', width: 120,
            render: (cat) => cat ? <Tag color={cat.color}>{cat.name}</Tag> : <Tag>—</Tag> },
        { title: 'Rekening', dataIndex: 'account', key: 'account', width: 120,
            render: (acc) => acc?.name || '—' },
        { title: 'Aksi', key: 'actions', width: 60,
            render: (_, record) => (
                <Button
                    icon={<DeleteOutlined />}
                    size="small"
                    danger
                    onClick={() => handleDelete(record)}
                />
            ),
        },
    ];

    return (
        <App>
            <AuthenticatedLayout title="Transaksi">
                <Head title="Transaksi" />
                <Table
                    dataSource={transactions}
                    columns={columns}
                    rowKey="id"
                    size="small"
                    pagination={{ pageSize: 25, showSizeChanger: true, showTotal: (t) => `${t} transaksi` }}
                    scroll={{ x: 750 }}
                />
            </AuthenticatedLayout>
        </App>
    );
}
