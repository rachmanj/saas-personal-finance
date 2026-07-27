import { useState } from 'react';
import { Dropdown, Button, Modal, Input, App, Space } from 'antd';
import {
    DownloadOutlined,
    FilePdfOutlined,
    FileExcelOutlined,
    FileTextOutlined,
    GoogleOutlined,
} from '@ant-design/icons';
import { apiGet, apiPost } from '../../utils/api';

export default function ReportExport() {
    const { message } = App.useApp();
    const [loading, setLoading] = useState(null);
    const [sheetsModalOpen, setSheetsModalOpen] = useState(false);
    const [spreadsheetId, setSpreadsheetId] = useState('');

    const handlePdf = async () => {
        setLoading('pdf');
        try {
            const res = await apiGet('/api/export/pdf');
            message.success(res.message || 'PDF generation started');
            if (res.data?.job_id) {
                const filename = res.data.job_id.replace('exports/', '');
                window.open(`/api/export/download/pdf/${filename}`, '_blank');
            }
        } catch (err) {
            message.error(err.message || 'PDF export failed');
        } finally {
            setLoading(null);
        }
    };

    const handleCsv = async () => {
        setLoading('csv');
        try {
            const res = await apiGet('/api/export/csv');
            message.success(res.message || 'CSV export started');
            if (res.data?.job_id) {
                const filename = res.data.job_id.replace('exports/', '');
                window.open(`/api/export/download/csv/${filename}`, '_blank');
            }
        } catch (err) {
            message.error(err.message || 'CSV export failed');
        } finally {
            setLoading(null);
        }
    };

    const handleOfx = () => {
        setLoading('ofx');
        window.open('/api/export/ofx', '_blank');
        setLoading(null);
    };

    const handleGoogleSheets = async () => {
        if (!spreadsheetId.trim()) {
            message.error('Please enter a spreadsheet ID');
            return;
        }

        setLoading('sheets');
        try {
            const res = await apiPost('/api/export/google-sheets', {
                spreadsheet_id: spreadsheetId.trim(),
            });
            message.success(res.message || 'Google Sheets sync started');
            setSheetsModalOpen(false);
            setSpreadsheetId('');
        } catch (err) {
            message.error(err.message || 'Google Sheets sync failed');
        } finally {
            setLoading(null);
        }
    };

    const menuItems = [
        {
            key: 'pdf',
            icon: <FilePdfOutlined />,
            label: 'Export as PDF',
            onClick: handlePdf,
        },
        {
            key: 'csv',
            icon: <FileExcelOutlined />,
            label: 'Export as CSV',
            onClick: handleCsv,
        },
        {
            key: 'ofx',
            icon: <FileTextOutlined />,
            label: 'Export as OFX',
            onClick: handleOfx,
        },
        {
            key: 'sheets',
            icon: <GoogleOutlined />,
            label: 'Sync to Google Sheets',
            onClick: () => setSheetsModalOpen(true),
        },
    ];

    return (
        <Space>
            <Dropdown menu={{ items: menuItems }} trigger={['click']}>
                <Button type="primary" icon={<DownloadOutlined />} loading={!!loading}>
                    Export
                </Button>
            </Dropdown>

            <Modal
                title="Sync to Google Sheets"
                open={sheetsModalOpen}
                onCancel={() => setSheetsModalOpen(false)}
                onOk={handleGoogleSheets}
                confirmLoading={loading === 'sheets'}
                okText="Sync"
            >
                <Input
                    placeholder="Spreadsheet ID"
                    value={spreadsheetId}
                    onChange={(e) => setSpreadsheetId(e.target.value)}
                />
            </Modal>
        </Space>
    );
}
