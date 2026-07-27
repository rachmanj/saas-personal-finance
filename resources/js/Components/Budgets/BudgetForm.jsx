import { useState, useEffect } from 'react';
import { Modal, Form, InputNumber, Select, DatePicker, App } from 'antd';
import { apiGet, apiPost, apiPut } from '../../utils/api';
import dayjs from 'dayjs';

const PERIODS = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
    { value: 'custom', label: 'Custom' },
];

const CURRENCIES = [
    { value: 'USD', label: 'USD' },
    { value: 'EUR', label: 'EUR' },
    { value: 'GBP', label: 'GBP' },
    { value: 'IDR', label: 'IDR' },
];

/**
 * @param {{
 *   open: boolean,
 *   onClose: () => void,
 *   onSuccess?: () => void,
 *   budget?: object | null,
 * }} props
 */
export default function BudgetForm({ open, onClose, onSuccess, budget = null }) {
    const [form] = Form.useForm();
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState([]);
    const [period, setPeriod] = useState('monthly');

    const isEdit = !!budget;

    useEffect(() => {
        if (open) {
            apiGet('/api/categories')
                .then((res) => setCategories(res.data || []))
                .catch(() => message.error('Failed to load categories'));

            if (budget) {
                form.setFieldsValue({
                    ...budget,
                    start_date: budget.start_date ? dayjs(budget.start_date) : dayjs(),
                    end_date: budget.end_date ? dayjs(budget.end_date) : null,
                });
                setPeriod(budget.period || 'monthly');
            } else {
                form.resetFields();
                setPeriod('monthly');
            }
        }
    }, [open, budget, form, message]);

    const handleSubmit = async (values) => {
        setLoading(true);
        try {
            const payload = {
                ...values,
                start_date: values.start_date?.format('YYYY-MM-DD'),
                end_date: values.end_date?.format('YYYY-MM-DD') || null,
            };

            if (payload.period !== 'custom') {
                delete payload.end_date;
            }

            if (isEdit) {
                await apiPut(`/api/budgets/${budget.id}`, payload);
                message.success('Budget updated!');
            } else {
                await apiPost('/api/budgets', payload);
                message.success('Budget created!');
            }

            form.resetFields();
            onSuccess?.();
        } catch (err) {
            message.error(err.message || 'Failed to save budget');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            title={isEdit ? 'Edit Budget' : 'New Budget'}
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            confirmLoading={loading}
            destroyOnClose
            width={520}
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={handleSubmit}
                initialValues={{
                    currency: 'USD',
                    period: 'monthly',
                    start_date: dayjs(),
                    notification_threshold: 80,
                }}
            >
                <Form.Item name="category_id" label="Category" rules={[{ required: true, message: 'Select a category' }]}>
                    <Select
                        showSearch
                        filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                        options={categories.map((c) => ({ value: c.id, label: c.name }))}
                    />
                </Form.Item>

                <Form.Item name="amount" label="Amount" rules={[{ required: true, message: 'Amount is required' }]}>
                    <InputNumber style={{ width: '100%' }} min={0.01} precision={2} placeholder="0.00" />
                </Form.Item>

                <Form.Item name="currency" label="Currency" rules={[{ required: true }]}>
                    <Select options={CURRENCIES} />
                </Form.Item>

                <Form.Item name="period" label="Period" rules={[{ required: true }]}>
                    <Select options={PERIODS} onChange={setPeriod} />
                </Form.Item>

                <Form.Item name="start_date" label="Start Date" rules={[{ required: true }]}>
                    <DatePicker style={{ width: '100%' }} />
                </Form.Item>

                {period === 'custom' && (
                    <Form.Item name="end_date" label="End Date" rules={[{ required: true, message: 'End date is required for custom period' }]}>
                        <DatePicker style={{ width: '100%' }} />
                    </Form.Item>
                )}

                <Form.Item
                    name="notification_threshold"
                    label="Notification Threshold (%)"
                    rules={[{ required: true }, { type: 'number', min: 1, max: 100 }]}
                >
                    <InputNumber style={{ width: '100%' }} min={1} max={100} />
                </Form.Item>
            </Form>
        </Modal>
    );
}
