import { useEffect, useState } from 'react';
import { Upload, Select, Typography, Space, Button, App } from 'antd';
import { UploadOutlined, InboxOutlined } from '@ant-design/icons';
import { apiGet } from '../../utils/api';

const { Dragger } = Upload;
const { Text } = Typography;

function formatFileSize(bytes) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * @param {{ onUpload: (file: File, accountId: number) => void, uploading?: boolean }} props
 */
export default function ImportUploader({ onUpload, uploading = false }) {
    const [accounts, setAccounts] = useState([]);
    const [accountId, setAccountId] = useState(null);
    const [selectedFile, setSelectedFile] = useState(null);
    const { message } = App.useApp();

    useEffect(() => {
        apiGet('/api/accounts')
            .then((res) => setAccounts(res.data || []))
            .catch(() => message.error('Gagal memuat daftar akun'));
    }, [message]);

    const handleBeforeUpload = (file) => {
        const extension = file.name.split('.').pop()?.toLowerCase();
        if (!['csv', 'ofx'].includes(extension)) {
            message.error('Hanya file CSV dan OFX yang didukung');
            return Upload.LIST_IGNORE;
        }
        setSelectedFile(file);
        return false;
    };

    const handleUpload = () => {
        if (!accountId) {
            message.warning('Pilih akun terlebih dahulu');
            return;
        }
        if (!selectedFile) {
            message.warning('Pilih file terlebih dahulu');
            return;
        }
        onUpload(selectedFile, accountId);
    };

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <div>
                <Text strong>Akun Tujuan</Text>
                <Select
                    style={{ width: '100%', marginTop: 8 }}
                    placeholder="Pilih akun..."
                    value={accountId}
                    onChange={setAccountId}
                    options={accounts.map((a) => ({ value: a.id, label: a.name }))}
                    showSearch
                    optionFilterProp="label"
                />
            </div>

            <Dragger
                accept=".csv,.ofx"
                multiple={false}
                showUploadList={false}
                beforeUpload={handleBeforeUpload}
                disabled={uploading}
            >
                <p className="ant-upload-drag-icon">
                    <InboxOutlined />
                </p>
                <p className="ant-upload-text">Seret file ke sini atau klik untuk memilih</p>
                <p className="ant-upload-hint">Format yang didukung: CSV, OFX (maks. 10 MB)</p>
            </Dragger>

            {selectedFile && (
                <div>
                    <Text type="secondary">File terpilih: </Text>
                    <Text strong>{selectedFile.name}</Text>
                    <Text type="secondary"> ({formatFileSize(selectedFile.size)})</Text>
                </div>
            )}

            {selectedFile && accountId && (
                <Button
                    type="primary"
                    icon={<UploadOutlined />}
                    onClick={handleUpload}
                    loading={uploading}
                >
                    Unggah File
                </Button>
            )}
        </Space>
    );
}
