import { useEffect } from 'react';
import { Modal, Form, Input, InputNumber, Select, DatePicker, Switch, App } from 'antd';
import { apiPost, apiPut } from '../../utils/api';
import dayjs from 'dayjs';

const REMINDER_OPTIONS = [
    { value: 1, label: '1 day before' },
    { value: 3, label: '3 days before' },
    { value: 7, label: '7 days before' },
    { value: 14, label: '14 days before' },
    { value: 30, label: '30 days before' },
];

const FREQUENCY_OPTIONS = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
];

/**
 * @param {{
 *   open: boolean,
 *   onClose: () => void,
 *   onSuccess: () => void,
 *   reminder?: object | null,
 * }} props
 */
export default function ReminderForm({ open, onClose, onSuccess, reminder }) {
    const { message } = App.useApp();
    const [form] = Form.useForm();
    const isEditing = !!reminder;

    useEffect(() => {
        if (open) {
            if (reminder) {
                form.setFieldsValue({
                    name: reminder.name,
                    amount: Number(reminder.amount),
                    currency: reminder.currency,
                    due_date: reminder.due_date ? dayjs(reminder.due_date) : null,
                    reminder_days_before: reminder.reminder_days_before || [1, 3, 7],
                    is_recurring: reminder.is_recurring || false,
                    frequency: reminder.frequency,
                });
            } else {
                form.resetFields();
                form.setFieldsValue({
                    currency: 'USD',
                    reminder_days_before: [1, 3, 7],
                    is_recurring: false,
                });
            }
        }
    }, [open, reminder, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            const payload = {
                ...values,
                due_date: values.due_date.format('YYYY-MM-DD'),
            };

            if (isEditing) {
                await apiPut(`/api/bill-reminders/${reminder.id}`, payload);
                message.success('Reminder updated');
            } else {
                await apiPost('/api/bill-reminders', payload);
                message.success('Reminder created');
            }

            onSuccess();
        } catch (err) {
            if (err.errors) {
                const fields = Object.entries(err.errors).map(([name, errors]) => ({
                    name,
                    errors,
                }));
                form.setFields(fields);
            } else if (err.message) {
                message.error(err.message);
            }
        }
    };

    return (
        <Modal
            title={isEditing ? 'Edit Bill Reminder' : 'Add Bill Reminder'}
            open={open}
            onCancel={onClose}
            onOk={handleSubmit}
            okText={isEditing ? 'Update' : 'Create'}
            destroyOnHidden
        >
            <Form form={form} layout="vertical">
                <Form.Item name="name" label="Name" rules={[{ required: true, message: 'Name is required' }]}>
                    <Input placeholder="e.g. Electric Bill" />
                </Form.Item>

                <Form.Item name="amount" label="Amount" rules={[{ required: true, message: 'Amount is required' }]}>
                    <InputNumber min={0} step={0.01} style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item name="currency" label="Currency" rules={[{ required: true, message: 'Currency is required' }]}>
                    <Input maxLength={3} placeholder="USD" />
                </Form.Item>

                <Form.Item name="due_date" label="Due Date" rules={[{ required: true, message: 'Due date is required' }]}>
                    <DatePicker style={{ width: '100%' }} disabledDate={(d) => d && d < dayjs().endOf('day')} />
                </Form.Item>

                <Form.Item
                    name="reminder_days_before"
                    label="Remind Me"
                    rules={[{ required: true, message: 'Select at least one reminder' }]}
                >
                    <Select mode="multiple" options={REMINDER_OPTIONS} placeholder="Select reminder days" />
                </Form.Item>

                <Form.Item name="is_recurring" label="Recurring" valuePropName="checked">
                    <Switch />
                </Form.Item>

                <Form.Item noStyle shouldUpdate={(prev, curr) => prev.is_recurring !== curr.is_recurring}>
                    {({ getFieldValue }) =>
                        getFieldValue('is_recurring') ? (
                            <Form.Item name="frequency" label="Frequency" rules={[{ required: true, message: 'Frequency is required' }]}>
                                <Select options={FREQUENCY_OPTIONS} placeholder="Select frequency" />
                            </Form.Item>
                        ) : null
                    }
                </Form.Item>
            </Form>
        </Modal>
    );
}
