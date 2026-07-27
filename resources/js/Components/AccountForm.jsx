import { Form, Input, Select, Switch, InputNumber } from 'antd';

const ACCOUNT_TYPES = [
    { value: 'checking', label: 'Checking' },
    { value: 'savings', label: 'Savings' },
    { value: 'credit_card', label: 'Credit Card' },
    { value: 'cash', label: 'Cash' },
    { value: 'investment', label: 'Investment' },
];

const CURRENCIES = [
    { value: 'USD', label: 'USD' },
    { value: 'EUR', label: 'EUR' },
    { value: 'GBP', label: 'GBP' },
    { value: 'IDR', label: 'IDR' },
];

const ICONS = [
    { value: 'bank', label: 'Bank' },
    { value: 'wallet', label: 'Wallet' },
    { value: 'credit-card', label: 'Credit Card' },
    { value: 'cash', label: 'Cash' },
    { value: 'piggy-bank', label: 'Piggy Bank' },
];

/**
 * @param {{ form: import('antd').FormInstance, initialValues?: object }} props
 */
export default function AccountForm({ form, initialValues = {} }) {
    return (
        <Form form={form} layout="vertical" initialValues={{
            currency: 'USD',
            include_in_net_worth: true,
            is_active: true,
            ...initialValues,
        }}>
            <Form.Item name="name" label="Name" rules={[{ required: true, message: 'Name is required' }]}>
                <Input />
            </Form.Item>

            <Form.Item name="type" label="Type" rules={[{ required: true, message: 'Type is required' }]}>
                <Select options={ACCOUNT_TYPES} />
            </Form.Item>

            <Form.Item name="currency" label="Currency" rules={[{ required: true, message: 'Currency is required' }]}>
                <Select options={CURRENCIES} />
            </Form.Item>

            <Form.Item name="initial_balance" label="Initial Balance">
                <InputNumber style={{ width: '100%' }} min={0} precision={2} />
            </Form.Item>

            <Form.Item name="include_in_net_worth" label="Include in Net Worth" valuePropName="checked">
                <Switch />
            </Form.Item>

            <Form.Item name="is_active" label="Active" valuePropName="checked">
                <Switch />
            </Form.Item>

            <Form.Item name="color" label="Color">
                <Input placeholder="#FF0000" maxLength={7} />
            </Form.Item>

            <Form.Item name="icon" label="Icon">
                <Select options={ICONS} allowClear />
            </Form.Item>
        </Form>
    );
}
