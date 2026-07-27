import { useState, useEffect } from 'react';
import { Form, Input, InputNumber, DatePicker, Button, Spin, App, Select, Space } from 'antd';
import { apiGet, apiPost } from '../../utils/api';
import { fetchCategorySuggestion } from '../../utils/categorySuggestion';
import ConfidenceBadge from '../Shared/ConfidenceBadge';
import dayjs from 'dayjs';

export default function OCRPreview({ jobId, onSuccess, onCancel }) {
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [categories, setCategories] = useState([]);
    const [categoryConfidence, setCategoryConfidence] = useState(null);
    const [form] = Form.useForm();
    const { message } = App.useApp();

    useEffect(() => {
        apiGet('/api/categories')
            .then((res) => setCategories(res.data || []))
            .catch(() => {});
    }, []);

    const applyCategorySuggestion = async (description) => {
        if (!description || description.length < 2) {
            setCategoryConfidence(null);
            return;
        }

        try {
            const suggestion = await fetchCategorySuggestion(description);
            if (suggestion.categoryId) {
                form.setFieldsValue({ category_id: suggestion.categoryId });
            }
            setCategoryConfidence(suggestion.confidence);
        } catch {
            setCategoryConfidence(null);
        }
    };

    useEffect(() => {
        let attempts = 0;
        const maxAttempts = 30;
        const poll = async () => {
            try {
                const res = await fetch(`/api/transactions/ocr/${jobId}/status`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                const job = data.data;
                attempts++;
                if (job.status === 'completed') {
                    setLoading(false);
                    const description = job.result?.merchant || '';
                    form.setFieldsValue({
                        description,
                        amount: job.result?.amount ? job.result.amount / 100 : 0,
                        transaction_date: dayjs(job.result?.date || new Date()),
                    });
                    await applyCategorySuggestion(description);
                } else if (job.status === 'failed') {
                    setLoading(false);
                    message.error('OCR processing failed');
                } else if (attempts >= maxAttempts) {
                    setLoading(false);
                    message.warning('OCR processing timed out');
                } else {
                    setTimeout(poll, 2000);
                }
            } catch {
                setLoading(false);
                message.error('Failed to check OCR status');
            }
        };
        poll();
    }, [jobId, form, message]);

    const handleSubmit = async (values) => {
        setSubmitting(true);
        try {
            await apiPost('/api/transactions', {
                ...values,
                type: 'expense',
                transaction_date: values.transaction_date?.format('YYYY-MM-DD'),
                source: 'ocr',
            });
            message.success('Transaction created from receipt!');
            onSuccess?.();
        } catch (err) {
            message.error(err.message || 'Failed to create transaction');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: 24 }} role="status" aria-live="polite">
                <Spin tip="Processing receipt..." />
            </div>
        );
    }

    return (
        <Form form={form} layout="vertical" onFinish={handleSubmit}>
            <Form.Item name="description" label="Description" rules={[{ required: true }]}>
                <Input onBlur={(e) => applyCategorySuggestion(e.target.value)} />
            </Form.Item>
            <Form.Item name="amount" label="Amount" rules={[{ required: true }]}>
                <InputNumber style={{ width: '100%' }} min={0.01} precision={2} />
            </Form.Item>
            <Form.Item name="transaction_date" label="Date">
                <DatePicker style={{ width: '100%' }} />
            </Form.Item>
            <Form.Item
                name="category_id"
                label={
                    <Space>
                        Category
                        <ConfidenceBadge confidence={categoryConfidence} />
                    </Space>
                }
            >
                <Select
                    allowClear
                    showSearch
                    filterOption={(input, option) =>
                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                    }
                    options={categories.map((c) => ({ value: c.id, label: c.name }))}
                    placeholder="Select category"
                />
            </Form.Item>
            <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
                <Button onClick={onCancel}>Cancel</Button>
                <Button type="primary" htmlType="submit" loading={submitting}>Save Transaction</Button>
            </div>
        </Form>
    );
}
