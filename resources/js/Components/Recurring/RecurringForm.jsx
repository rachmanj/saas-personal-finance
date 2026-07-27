import { useState, useEffect } from 'react';
import { Modal, Form, Input, InputNumber, Select, DatePicker, Switch, App } from 'antd';
import { apiGet, apiPost, apiPut } from '../../utils/api';
import dayjs from 'dayjs';

const TRANSACTION_TYPES = [
    { value: 'income', label: 'Income' },
    { value: 'expense', label: 'Expense' },
    { value: 'transfer', label: 'Transfer' },
];

const FREQUENCIES = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
    { value: 'custom', label: 'Custom' },
];

const TEMPLATE_TYPES = [
    { value: 'subscription', label: 'Subscription' },
    { value: 'bill', label: 'Bill' },
    { value: 'salary', label: 'Salary' },
    { value: 'rent', label: 'Rent' },
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
 *   recurring?: object | null,
 * }} props
 */
export default function RecurringForm({ open, onClose, onSuccess, recurring = null }) {
    const [form] = Form.useForm();
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
    const [accounts, setAccounts] = useState([]);
    const [categories, setCategories] = useState([]);

    const isEdit = !!recurring;

    useEffect(() => {
        if (open) {
            Promise.all([apiGet('/api/accounts'), apiGet('/api/categories')])
                .then(([accRes, catRes]) => {
                    setAccounts(accRes.data || []);
                    setCategories(catRes.data || []);
                })
                .catch(() => message.error('Failed to load form data'));

            if (recurring) {
                form.setFieldsValue({
                    ...recurring,
                    start_date: recurring.start_date ? dayjs(recurring.start_date) : dayjs(),
                    end_date: recurring.end_date ? dayjs(recurring.end_date) : null,
                });
            } else {
                form.resetFields();
            }
        }
    }, [open, recurring, form, message]);

    const handleSubmit = async (values) => {
        setLoading(true);
        try {
            const payload = {
                ...values,
                start_date: values.start_date?.format('YYYY-MM-DD'),
                end_date: values.end_date?.format('YYYY-MM-DD') || null,
                interval: values.interval ?? 1,
            };

            if (isEdit) {
                await apiPut(`/api/recurring-transactions/${recurring.id}`, payload);
                message.success('Recurring transaction updated!');
            } else {
                await apiPost('/api/recurring-transactions', payload);
                message.success('Recurring transaction created!');
            }

            form.resetFields();
            onSuccess?.();
        } catch (err) {
            message.error(err.message || 'Failed to save recurring transaction');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            title={isEdit ? 'Edit Recurring Transaction' : 'New Recurring Transaction'}
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            confirmLoading={loading}
            destroyOnClose
            width={640}
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={handleSubmit}
                initialValues={{
                    type: 'expense',
                    currency: 'USD',
                    frequency: 'monthly',
                    interval: 1,
                    start_date: dayjs(),
                    template_type: 'custom',
                    is_active: true,
                }}
            >
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

                <Form.Item name="type" label="Type" rules={[{ required: true }]}>
                    <Select options={TRANSACTION_TYPES} />
                </Form.Item>

                <Form.Item name="amount" label="Amount" rules={[{ required: true, message: 'Amount is required' }]}>
                    <InputNumber style={{ width: '100%' }} min={0.01} precision={2} placeholder="0.00" />
                </Form.Item>

                <Form.Item name="currency" label="Currency" rules={[{ required: true }]}>
                    <Select options={CURRENCIES} />
                </Form.Item>

                <Form.Item name="description" label="Description">
                    <Input placeholder="e.g. Netflix subscription" />
                </Form.Item>

                <Form.Item name="frequency" label="Frequency" rules={[{ required: true }]}>
                    <Select options={FREQUENCIES} />
                </Form.Item>

                <Form.Item name="interval" label="Interval" rules={[{ required: true, type: 'number', min: 1 }]}>
                    <InputNumber style={{ width: '100%' }} min={1} />
                </Form.Item>

                <Form.Item name="start_date" label="Start Date" rules={[{ required: true }]}>
                    <DatePicker style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item name="end_date" label="End Date">
                    <DatePicker style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item name="template_type" label="Template Type">
                    <Select options={TEMPLATE_TYPES} />
                </Form.Item>

                <Form.Item name="is_active" label="Active" valuePropName="checked">
                    <Switch />
                </Form.Item>
            </Form>
        </Modal>
    );
}
