import { Form, Input, Select, Switch } from 'antd';

/**
 * @param {{ form: import('antd').FormInstance, initialValues?: object, categories?: Array }} props
 */
export default function CategoryForm({ form, initialValues = {}, categories = [] }) {
    const parentOptions = categories
        .filter((c) => c.id !== initialValues.id)
        .map((c) => ({ value: c.id, label: c.name }));

    return (
        <Form form={form} layout="vertical" initialValues={{
            is_active: true,
            ...initialValues,
        }}>
            <Form.Item name="name" label="Name" rules={[{ required: true, message: 'Name is required' }]}>
                <Input />
            </Form.Item>

            <Form.Item name="type" label="Type" rules={[{ required: true, message: 'Type is required' }]}>
                <Select options={[
                    { value: 'income', label: 'Income' },
                    { value: 'expense', label: 'Expense' },
                ]} />
            </Form.Item>

            <Form.Item name="parent_id" label="Parent Category">
                <Select options={parentOptions} allowClear placeholder="None" />
            </Form.Item>

            <Form.Item name="color" label="Color">
                <Input placeholder="#FF0000" maxLength={7} />
            </Form.Item>

            <Form.Item name="icon" label="Icon">
                <Input maxLength={50} />
            </Form.Item>

            <Form.Item name="is_active" label="Active" valuePropName="checked">
                <Switch />
            </Form.Item>
        </Form>
    );
}
