import { useState, useEffect } from 'react';
import { Modal, Tabs, Form, Input, InputNumber, Select, DatePicker, App, Button } from 'antd';
import { CameraOutlined, AudioOutlined, EditOutlined } from '@ant-design/icons';
import { apiGet, apiPost } from '../../utils/api';
import dayjs from 'dayjs';
import ReceiptCapture from './ReceiptCapture';
import OCRPreview from './OCRPreview';
import VoiceInput from './VoiceInput';
import VoicePreview from './VoicePreview';

const TRANSACTION_TYPES = [
    { value: 'income', label: 'Income' },
    { value: 'expense', label: 'Expense' },
    { value: 'transfer', label: 'Transfer' },
];

const CURRENCIES = [
    { value: 'USD', label: 'USD' },
    { value: 'EUR', label: 'EUR' },
    { value: 'GBP', label: 'GBP' },
    { value: 'IDR', label: 'IDR' },
];

export default function QuickAddModal({ open, onClose, onSuccess }) {
    const [form] = Form.useForm();
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
    const [accounts, setAccounts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [activeTab, setActiveTab] = useState('manual');
    const [ocrJobId, setOcrJobId] = useState(null);
    const [voiceJobId, setVoiceJobId] = useState(null);

    useEffect(() => {
        if (open) {
            apiGet('/api/accounts').then((res) => setAccounts(res.data || [])).catch(() => {});
            apiGet('/api/categories').then((res) => setCategories(res.data || [])).catch(() => {});
        }
    }, [open]);

    const handleSubmit = async (values) => {
        setLoading(true);
        try {
            await apiPost('/api/transactions', {
                ...values,
                transaction_date: values.transaction_date?.format('YYYY-MM-DD') || dayjs().format('YYYY-MM-DD'),
            });
            message.success('Transaction added!');
            form.resetFields();
            onSuccess?.();
        } catch (err) {
            message.error(err.message || 'Failed to add transaction');
        } finally {
            setLoading(false);
        }
    };

    const handleOcrCapture = async (photoDataUrl) => {
        try {
            const res = await fetch(photoDataUrl);
            const blob = await res.blob();
            const formData = new FormData();
            formData.append('receipt', blob, 'receipt.jpg');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const uploadRes = await fetch('/api/transactions/ocr', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: formData,
            });
            const data = await uploadRes.json();
            if (uploadRes.ok) {
                setOcrJobId(data.data.id);
            } else {
                message.error(data.message || 'Upload failed');
            }
        } catch {
            message.error('Failed to upload receipt');
        }
    };

    const handleVoiceRecording = async (blob) => {
        try {
            const formData = new FormData();
            formData.append('audio', blob, 'recording.webm');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const uploadRes = await fetch('/api/transactions/voice', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: formData,
            });
            const data = await uploadRes.json();
            if (uploadRes.ok) {
                setVoiceJobId(data.data.id);
            } else {
                message.error(data.message || 'Upload failed');
            }
        } catch {
            message.error('Failed to upload voice recording');
        }
    };

    const handleClose = () => {
        setActiveTab('manual');
        setOcrJobId(null);
        setVoiceJobId(null);
        form.resetFields();
        onClose();
    };

    const handleSuccess = () => {
        handleClose();
        onSuccess?.();
    };

    const manualForm = (
        <Form
            form={form}
            layout="vertical"
            onFinish={handleSubmit}
            initialValues={{
                type: 'expense',
                transaction_date: dayjs(),
            }}
        >
            <Form.Item name="type" label="Type" rules={[{ required: true }]}>
                <Select options={TRANSACTION_TYPES} />
            </Form.Item>

            <Form.Item name="amount" label="Amount" rules={[{ required: true, message: 'Amount is required' }]}>
                <InputNumber style={{ width: '100%' }} min={0.01} precision={2} placeholder="0.00" />
            </Form.Item>

            <Form.Item name="description" label="Description" rules={[{ required: true, message: 'Description is required' }]}>
                <Input placeholder="What was this for?" />
            </Form.Item>

            <Form.Item name="account_id" label="Account" rules={[{ required: true, message: 'Select an account' }]}>
                <Select
                    showSearch
                    filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                    options={accounts.map((a) => ({ value: a.id, label: a.name }))}
                />
            </Form.Item>

            <Form.Item name="category_id" label="Category">
                <Select
                    allowClear
                    showSearch
                    filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                    options={categories.map((c) => ({ value: c.id, label: c.name }))}
                />
            </Form.Item>

            <Form.Item name="currency" label="Currency" rules={[{ required: true }]}>
                <Select options={CURRENCIES} />
            </Form.Item>

            <Form.Item name="transaction_date" label="Date">
                <DatePicker style={{ width: '100%' }} />
            </Form.Item>
        </Form>
    );

    const tabItems = [
        {
            key: 'manual',
            label: <span><EditOutlined /> Manual</span>,
            children: manualForm,
        },
        {
            key: 'ocr',
            label: <span><CameraOutlined /> OCR</span>,
            children: ocrJobId
                ? <OCRPreview jobId={ocrJobId} onSuccess={handleSuccess} onCancel={() => setOcrJobId(null)} />
                : <ReceiptCapture onCapture={handleOcrCapture} />,
        },
        {
            key: 'voice',
            label: <span><AudioOutlined /> Voice</span>,
            children: voiceJobId
                ? <VoicePreview jobId={voiceJobId} onSuccess={handleSuccess} onCancel={() => setVoiceJobId(null)} />
                : <VoiceInput onRecordingComplete={handleVoiceRecording} />,
        },
    ];

    return (
        <Modal
            title="Quick Add Transaction"
            open={open}
            onCancel={handleClose}
            footer={activeTab === 'manual' ? [
                <Button key="cancel" onClick={handleClose}>Cancel</Button>,
                <Button key="submit" type="primary" loading={loading} onClick={() => form.submit()}>Add Transaction</Button>,
            ] : null}
            destroyOnClose
            width={520}
        >
            <Tabs activeKey={activeTab} onChange={setActiveTab} items={tabItems} />
        </Modal>
    );
}
