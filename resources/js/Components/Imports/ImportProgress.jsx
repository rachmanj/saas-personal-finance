import { useMemo } from 'react';
import { Progress, Typography, Space, Alert, Spin } from 'antd';
import { CheckCircleOutlined, CloseCircleOutlined } from '@ant-design/icons';
import usePollingJob from '../../Hooks/usePollingJob';

const { Text, Title } = Typography;

const STATUS_LABELS = {
    pending: 'Menunggu...',
    processing: 'Memproses...',
    completed: 'Selesai',
    failed: 'Gagal',
};

const STATUS_PERCENT = {
    pending: 10,
    processing: 50,
    completed: 100,
    failed: 100,
};

/**
 * @param {{ importId: number }} props
 */
export default function ImportProgress({ importId }) {
    const { data, loading, error } = usePollingJob(`/api/imports/${importId}`, {
        enabled: true,
        maxAttempts: 60,
    });

    const status = data?.status || 'pending';
    const statusLabel = STATUS_LABELS[status] || status;
    const percent = STATUS_PERCENT[status] || 0;
    const isCompleted = status === 'completed';
    const isFailed = status === 'failed';

    const progressStatus = useMemo(() => {
        if (isFailed) return 'exception';
        if (isCompleted) return 'success';
        return 'active';
    }, [isCompleted, isFailed]);

    if (loading && !data) {
        return (
            <div style={{ textAlign: 'center', padding: 48 }}>
                <Spin size="large" tip="Memuat status import..." />
            </div>
        );
    }

    if (error) {
        return (
            <Alert
                type="error"
                showIcon
                message="Gagal memuat status import"
                description={error.message}
            />
        );
    }

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <div>
                <Title level={4} style={{ marginBottom: 16 }}>
                    {isCompleted && <CheckCircleOutlined style={{ color: '#52c41a', marginRight: 8 }} />}
                    {isFailed && <CloseCircleOutlined style={{ color: '#ff4d4f', marginRight: 8 }} />}
                    Status: {statusLabel}
                </Title>
                <Progress percent={percent} status={progressStatus} />
            </div>

            <Space direction="vertical" size="small">
                <Text>Total baris: <Text strong>{data?.total_rows ?? 0}</Text></Text>
                <Text>Diimpor: <Text strong style={{ color: '#52c41a' }}>{data?.imported_rows ?? 0}</Text></Text>
                <Text>Dilewati: <Text strong style={{ color: '#faad14' }}>{data?.skipped_rows ?? 0}</Text></Text>
            </Space>

            {isCompleted && (
                <Alert
                    type="success"
                    showIcon
                    message="Import berhasil!"
                    description={`${data?.imported_rows ?? 0} transaksi diimpor, ${data?.skipped_rows ?? 0} dilewati.`}
                />
            )}

            {isFailed && (
                <Alert
                    type="error"
                    showIcon
                    message="Import gagal"
                    description={data?.error_log?.message || 'Terjadi kesalahan saat memproses file.'}
                />
            )}
        </Space>
    );
}
