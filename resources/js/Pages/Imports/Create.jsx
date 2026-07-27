import { useState, useCallback } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Steps,
    Button,
    Card,
    Space,
    Typography,
    Descriptions,
    App,
    Spin,
} from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import ImportUploader from '../../Components/Imports/ImportUploader';
import CSVColumnMapper from '../../Components/Imports/CSVColumnMapper';
import ImportPreview from '../../Components/Imports/ImportPreview';
import ImportProgress from '../../Components/Imports/ImportProgress';
import { apiPost } from '../../utils/api';

const { Title, Text } = Typography;

async function uploadFile(file, accountId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    formData.append('file', file);
    formData.append('account_id', accountId);

    const response = await fetch('/api/imports/upload', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    });

    const data = await response.json();

    if (!response.ok) {
        const error = new Error(data.message || 'Upload gagal');
        error.errors = data.errors;
        throw error;
    }

    return data;
}

export default function Create() {
    const { message } = App.useApp();
    const [currentStep, setCurrentStep] = useState(0);
    const [uploading, setUploading] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [importData, setImportData] = useState(null);
    const [fileType, setFileType] = useState(null);
    const [previewRows, setPreviewRows] = useState([]);
    const [columnMapping, setColumnMapping] = useState({});
    const [mappingValid, setMappingValid] = useState(false);
    const [selectedRows, setSelectedRows] = useState([]);
    const [accountName, setAccountName] = useState('');
    const [showProgress, setShowProgress] = useState(false);

    const isCsv = fileType === 'csv';

    const steps = isCsv
        ? [
              { title: 'Upload File' },
              { title: 'Pemetaan Kolom' },
              { title: 'Pratinjau' },
              { title: 'Konfirmasi' },
          ]
        : [
              { title: 'Upload File' },
              { title: 'Pratinjau' },
              { title: 'Konfirmasi' },
          ];

    const handleUpload = useCallback(async (file, accountId) => {
        setUploading(true);
        try {
            const res = await uploadFile(file, accountId);
            const data = res.data;
            setImportData(data);
            setFileType(data.file_type);

            if (data.file_type === 'csv') {
                setPreviewRows(data.preview?.rows || []);
            } else {
                setPreviewRows(data.preview?.transactions || []);
            }

            const accountsRes = await fetch('/api/accounts', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const accountsData = await accountsRes.json();
            const account = (accountsData.data || []).find((a) => a.id === accountId);
            setAccountName(account?.name || '');

            message.success('File berhasil diunggah');
            setCurrentStep(1);
        } catch (err) {
            message.error(err.message || 'Gagal mengunggah file');
        } finally {
            setUploading(false);
        }
    }, [message]);

    const handleMapping = useCallback((mapping) => {
        setColumnMapping(mapping);
    }, []);

    const handleConfirm = async () => {
        if (!importData?.id) return;

        setConfirming(true);
        try {
            if (isCsv) {
                await apiPost(`/api/imports/${importData.id}/confirm`, {
                    column_mapping: {
                        ...columnMapping,
                        selected_rows: selectedRows,
                    },
                });
            } else {
                await apiPost(`/api/imports/${importData.id}/process`, {});
            }
            message.success('Import sedang diproses');
            setShowProgress(true);
        } catch (err) {
            message.error(err.message || 'Gagal memulai import');
        } finally {
            setConfirming(false);
        }
    };

    const canGoNext = () => {
        if (currentStep === 0) return false;
        if (isCsv && currentStep === 1) return mappingValid;
        const previewStep = isCsv ? 2 : 1;
        if (currentStep === previewStep) return selectedRows.length > 0;
        return true;
    };

    const handleNext = () => {
        const confirmStep = isCsv ? 3 : 2;
        if (currentStep < confirmStep) {
            setCurrentStep((s) => s + 1);
        }
    };

    const handleBack = () => {
        if (currentStep > 0) {
            setCurrentStep((s) => s - 1);
        }
    };

    const renderStepContent = () => {
        if (showProgress) {
            return <ImportProgress importId={importData.id} />;
        }

        if (currentStep === 0) {
            return <ImportUploader onUpload={handleUpload} uploading={uploading} />;
        }

        if (isCsv && currentStep === 1) {
            return (
                <CSVColumnMapper
                    headers={importData?.preview?.headers || []}
                    onMapping={handleMapping}
                    onValidChange={setMappingValid}
                />
            );
        }

        const previewStep = isCsv ? 2 : 1;
        if (currentStep === previewStep) {
            return (
                <ImportPreview
                    rows={previewRows}
                    fileType={fileType}
                    columnMapping={columnMapping}
                    onSelectionChange={setSelectedRows}
                />
            );
        }

        const confirmStep = isCsv ? 3 : 2;
        if (currentStep === confirmStep) {
            return (
                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                    <Title level={4}>Ringkasan Import</Title>
                    <Descriptions bordered column={1} size="small">
                        <Descriptions.Item label="Tipe File">
                            {fileType?.toUpperCase()}
                        </Descriptions.Item>
                        <Descriptions.Item label="Akun">{accountName}</Descriptions.Item>
                        <Descriptions.Item label="Total Baris">
                            {importData?.total_rows ?? previewRows.length}
                        </Descriptions.Item>
                        <Descriptions.Item label="Baris Dipilih">
                            {selectedRows.length}
                        </Descriptions.Item>
                        {isCsv && (
                            <>
                                <Descriptions.Item label="Kolom Tanggal">
                                    {columnMapping.date}
                                </Descriptions.Item>
                                <Descriptions.Item label="Kolom Deskripsi">
                                    {columnMapping.description}
                                </Descriptions.Item>
                                <Descriptions.Item label="Kolom Jumlah">
                                    {columnMapping.amount}
                                </Descriptions.Item>
                            </>
                        )}
                    </Descriptions>
                    <Text type="secondary">
                        Klik &quot;Konfirmasi Import&quot; untuk memulai proses import transaksi.
                    </Text>
                </Space>
            );
        }

        return null;
    };

    const confirmStep = isCsv ? 3 : 2;
    const isConfirmStep = currentStep === confirmStep;

    return (
        <App>
            <AuthenticatedLayout title="Import Baru">
                <Head title="Import Baru" />

                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                    <Link href="/imports">
                        <Button icon={<ArrowLeftOutlined />}>Kembali ke Riwayat</Button>
                    </Link>

                    {!showProgress && (
                        <Steps current={currentStep} items={steps} />
                    )}

                    <Card>
                        {uploading ? (
                            <div style={{ textAlign: 'center', padding: 48 }}>
                                <Spin size="large" tip="Mengunggah file..." />
                            </div>
                        ) : (
                            renderStepContent()
                        )}
                    </Card>

                    {!showProgress && (
                        <Space>
                            {currentStep > 0 && (
                                <Button onClick={handleBack}>Kembali</Button>
                            )}
                            {!isConfirmStep && currentStep > 0 && (
                                <Button type="primary" onClick={handleNext} disabled={!canGoNext()}>
                                    Lanjut
                                </Button>
                            )}
                            {isConfirmStep && (
                                <Button
                                    type="primary"
                                    onClick={handleConfirm}
                                    loading={confirming}
                                    disabled={selectedRows.length === 0}
                                >
                                    Konfirmasi Import
                                </Button>
                            )}
                        </Space>
                    )}

                    {showProgress && (
                        <Link href="/imports">
                            <Button type="primary">Lihat Riwayat Import</Button>
                        </Link>
                    )}
                </Space>
            </AuthenticatedLayout>
        </App>
    );
}
