import { useState, useEffect, useCallback } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Table, Button, Tag, Space, Modal, Descriptions, Typography, App } from 'antd';
import { PlusOutlined, DeleteOutlined, EyeOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { apiGet, apiDelete } from '../../utils/api';

const { Text } = Typography;

const STATUS_CONFIG = {
    pending: { color: 'blue', label: 'Menunggu' },
    processing: { color: 'orange', label: 'Memproses' },
    completed: { color: 'green', label: 'Selesai' },
    failed: { color: 'red', label: 'Gagal' },
};

const FILE_TYPE_LABELS = {
    csv: 'CSV',
    ofx: 'OFX',
};

export default function Index() {
    const { message } = App.useApp();
    const { props: pageProps } = usePage();
    const [imports, setImports] = useState(pageProps.imports || []);
    const [loading, setLoading] = useState(!pageProps.imports);
    const [pagination, setPagination] = useState({
        current: pageProps.meta?.current_page || 1,
        pageSize: pageProps.meta?.per_page || 20,
        total: pageProps.meta?.total || 0,
    });
    const [detailRecord, setDetailRecord] = useState(null);

    const loadImports = useCallback(async (page = 1, pageSize = 20) => {
        setLoading(true);
        try {
            const res = await apiGet(`/api/imports?page=${page}&pageSize=${pageSize}`);
            setImports(res.data || []);
            setPagination({
                current: res.meta?.current_page || page,
                pageSize: res.meta?.per_page || pageSize,
                total: res.meta?.total || 0,
            });
        } catch {
            message.error('Gagal memuat riwayat import');
        } finally {
            setLoading(false);
        }
    }, [message]);

    useEffect(() => {
        if (!pageProps.imports) loadImports();
    }, [loadImports]);

    const handleTableChange = (pag) => {
        loadImports(pag.current, pag.pageSize);
    };

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Hapus import?',
            content: `Yakin ingin menghapus import ini? File dan data terkait akan dihapus.`,
            okText: 'Hapus',
            okType: 'danger',
            cancelText: 'Batal',
            onOk: async () => {
                try {
                    await apiDelete(`/api/imports/${record.id}`);
                    message.success('Import berhasil dihapus');
                    loadImports(pagination.current, pagination.pageSize);
                } catch {
                    message.error('Gagal menghapus import');
                }
            },
        });
    };

    const columns = [
        {
            title: 'Tanggal',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('DD MMM YYYY HH:mm'),
        },
        {
            title: 'Tipe File',
            dataIndex: 'file_type',
            key: 'file_type',
            render: (type) => FILE_TYPE_LABELS[type] || type?.toUpperCase(),
        },
        {
            title: 'Akun',
            key: 'account',
            render: (_, record) => record.account?.name || '-',
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status) => {
                const config = STATUS_CONFIG[status] || { color: 'default', label: status };
                return <Tag color={config.color}>{config.label}</Tag>;
            },
        },
        {
            title: 'Total Baris',
            dataIndex: 'total_rows',
            key: 'total_rows',
            align: 'right',
        },
        {
            title: 'Diimpor',
            dataIndex: 'imported_rows',
            key: 'imported_rows',
            align: 'right',
            render: (val) => <Text style={{ color: '#52c41a' }}>{val}</Text>,
        },
        {
            title: 'Dilewati',
            dataIndex: 'skipped_rows',
            key: 'skipped_rows',
            align: 'right',
            render: (val) => <Text style={{ color: '#faad14' }}>{val}</Text>,
        },
        {
            title: 'Aksi',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Button
                        icon={<EyeOutlined />}
                        size="small"
                        onClick={() => setDetailRecord(record)}
                    />
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
            <AuthenticatedLayout title="Riwayat Import">
                <Head title="Riwayat Import" />

                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                    <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                        <Link href="/imports/create">
                            <Button type="primary" icon={<PlusOutlined />}>
                                Import Baru
                            </Button>
                        </Link>
                    </div>

                    <Table
                        columns={columns}
                        dataSource={imports}
                        rowKey="id"
                        loading={loading}
                        pagination={{
                            current: pagination.current,
                            pageSize: pagination.pageSize,
                            total: pagination.total,
                            showSizeChanger: true,
                            showTotal: (total) => `Total ${total} import`,
                        }}
                        onChange={handleTableChange}
                    />
                </Space>

                <Modal
                    title="Detail Import"
                    open={!!detailRecord}
                    onCancel={() => setDetailRecord(null)}
                    footer={[
                        <Button key="close" onClick={() => setDetailRecord(null)}>
                            Tutup
                        </Button>,
                    ]}
                    width={600}
                >
                    {detailRecord && (
                        <Descriptions bordered column={1} size="small">
                            <Descriptions.Item label="ID">{detailRecord.id}</Descriptions.Item>
                            <Descriptions.Item label="Tanggal">
                                {dayjs(detailRecord.created_at).format('DD MMM YYYY HH:mm')}
                            </Descriptions.Item>
                            <Descriptions.Item label="Tipe File">
                                {FILE_TYPE_LABELS[detailRecord.file_type] || detailRecord.file_type}
                            </Descriptions.Item>
                            <Descriptions.Item label="Akun">
                                {detailRecord.account?.name || '-'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Status">
                                <Tag color={STATUS_CONFIG[detailRecord.status]?.color}>
                                    {STATUS_CONFIG[detailRecord.status]?.label || detailRecord.status}
                                </Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label="Total Baris">
                                {detailRecord.total_rows}
                            </Descriptions.Item>
                            <Descriptions.Item label="Diimpor">
                                {detailRecord.imported_rows}
                            </Descriptions.Item>
                            <Descriptions.Item label="Dilewati">
                                {detailRecord.skipped_rows}
                            </Descriptions.Item>
                            {detailRecord.status === 'failed' && detailRecord.error_log?.message && (
                                <Descriptions.Item label="Error">
                                    {detailRecord.error_log.message}
                                </Descriptions.Item>
                            )}
                        </Descriptions>
                    )}
                </Modal>
            </AuthenticatedLayout>
        </App>
    );
}
