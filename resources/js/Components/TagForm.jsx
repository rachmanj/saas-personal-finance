import { Form, Input } from 'antd';

/**
 * @param {{ form: import('antd').FormInstance, initialValues?: object }} props
 */
export default function TagForm({ form, initialValues = {} }) {
    return (
        <Form form={form} layout="vertical" initialValues={initialValues}>
            <Form.Item name="name" label="Name" rules={[{ required: true, message: 'Name is required' }]}>
                <Input />
            </Form.Item>

            <Form.Item name="color" label="Color">
                <Input placeholder="#FF0000" maxLength={7} />
            </Form.Item>
        </Form>
    );
}
