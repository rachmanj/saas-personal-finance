import { Head, Link, usePage, router } from '@inertiajs/react';
import { App, Table, Button, Space, Tag, Modal, message } from 'antd';
import {
    PlusOutlined,
    EditOutlined,
    ArrowUpOutlined,
    ArrowDownOutlined,
    SwapOutlined,
    DeleteOutlined,
} from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import dayjs from 'dayjs';

const typeColorMap = { income: 'green', expense: 'red', transfer: 'blue' };

export default function Index() {
    const { props } = usePage();
    const transactions = props.transactions || [];

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Hapus transaksi?',
            content: `Yakin ingin menghapus "${record.description}"?`,
            okText: 'Hapus',
            okType: 'danger',
            cancelText: 'Batal',
            onOk: () => {
                router.delete(`/transactions/${record.id}`);
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
        { title: 'Aksi', key: 'actions', width: 70,
            render: (_, record) => (
                <Space>
                    <Link href={`/transactions/${record.id}/edit`}>
                        <Button icon={<EditOutlined />} size="small" />
                    </Link>
                    <Button
                        icon={<DeleteOutlined />}
                        size="small"
                        danger
                        onClick={() => handleDelete(record)}
                    />
                </Space>
            ),
        },
    ];

    return (
        <App>
            <AuthenticatedLayout title="Transaksi">
                <Head title="Transaksi" />
                <div style={{ marginBottom: 16 }}>
                    <Link href="/transactions/create">
                        <Button type="primary" icon={<PlusOutlined />}>
                            Buat
                        </Button>
                    </Link>
                </div>
                <Table
                    dataSource={transactions}
                    columns={columns}
                    rowKey="id"
                    size="small"
                    pagination={{ pageSize: 25, showSizeChanger: true, showTotal: (t) => `${t} transaksi` }}
                    scroll={{ x: 820 }}
                />
            </AuthenticatedLayout>
        </App>
    );
}
