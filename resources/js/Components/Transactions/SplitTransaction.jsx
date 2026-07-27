import { Button, Form, Input, InputNumber, Select, Space, Tooltip, App } from 'antd';
import { PlusOutlined, DeleteOutlined } from '@ant-design/icons';

/**
 * Split transaction sub-form: dynamic Form.List with live remaining-amount validation.
 *
 * @param {{ transactionAmount?: number }} props
 */
export default function SplitTransaction({ transactionAmount = 0 }) {
    const { message } = App.useApp();

    const validator = (_, value) => {
        const form = document.querySelector('form');
        if (!form) return Promise.resolve();
        const splits = form.querySelectorAll('[id*="splits"]');
        const total = Array.from(splits).reduce((sum, el) => {
            const val = parseFloat(el.value);
            return sum + (isNaN(val) ? 0 : val);
        }, 0);

        if (total > transactionAmount && transactionAmount > 0) {
            return Promise.reject(new Error(`Splits total (${total.toFixed(2)}) exceeds transaction amount (${transactionAmount.toFixed(2)})`));
        }
        return Promise.resolve();
    };

    return (
        <Form.List name="splits">
            {(fields, { add, remove }) => (
                <>
                    {fields.map(({ key, name, ...restField }) => (
                        <Space key={key} style={{ display: 'flex', marginBottom: 8 }} align="baseline">
                            <Form.Item
                                {...restField}
                                name={[name, 'amount']}
                                rules={[
                                    { required: true, message: 'Amount required' },
                                    { validator },
                                ]}
                                style={{ marginBottom: 0 }}
                            >
                                <InputNumber placeholder="Amount" min={0.01} precision={2} style={{ width: 120 }} />
                            </Form.Item>

                            <Form.Item
                                {...restField}
                                name={[name, 'description']}
                                style={{ marginBottom: 0 }}
                            >
                                <Input placeholder="Description" style={{ width: 180 }} />
                            </Form.Item>

                            <Tooltip title="Remove split">
                                <Button
                                    type="text"
                                    danger
                                    icon={<DeleteOutlined />}
                                    onClick={() => remove(name)}
                                />
                            </Tooltip>
                        </Space>
                    ))}

                    <Button
                        type="dashed"
                        onClick={() => add({ amount: null, description: '' })}
                        block
                        icon={<PlusOutlined />}
                    >
                        Add Split
                    </Button>
                </>
            )}
        </Form.List>
    );
}
