import { Drawer, Descriptions, Tag, Space, List, Empty, Image } from 'antd';
import { ArrowUpOutlined, ArrowDownOutlined, SwapOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';

const typeColorMap = {
    income: 'green',
    expense: 'red',
    transfer: 'blue',
};

const typeIconMap = {
    income: <ArrowUpOutlined style={{ color: '#52c41a' }} />,
    expense: <ArrowDownOutlined style={{ color: '#ff4d4f' }} />,
    transfer: <SwapOutlined style={{ color: '#1677ff' }} />,
};

/**
 * @param {{
 *   open: boolean,
 *   onClose: () => void,
 *   transaction: object | null,
 *   onEdit?: (txn: object) => void,
 * }} props
 */
export default function TransactionDetail({ open, onClose, transaction, onEdit }) {
    if (!transaction) return null;

    const sign = transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '-' : '';

    return (
        <Drawer
            title="Transaction Detail"
            open={open}
            onClose={onClose}
            width={480}
            extra={
                onEdit && (
                    <a onClick={() => onEdit(transaction)}>Edit</a>
                )
            }
        >
            <Descriptions column={1} bordered size="small" style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Type">
                    <Tag color={typeColorMap[transaction.type]}>
                        {typeIconMap[transaction.type]}
                        {' '}{transaction.type?.charAt(0).toUpperCase() + transaction.type?.slice(1)}
                    </Tag>
                </Descriptions.Item>

                <Descriptions.Item label="Amount">
                    <span style={{ color: sign === '+' ? '#52c41a' : sign === '-' ? '#ff4d4f' : '#1677ff', fontWeight: 600 }}>
                        {sign}{Number(transaction.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                        {' '}{transaction.currency}
                    </span>
                </Descriptions.Item>

                <Descriptions.Item label="Base Amount">
                    {transaction.base_amount
                        ? `${Number(transaction.base_amount).toLocaleString('en-US', { minimumFractionDigits: 2 })} ${transaction.base_currency}`
                        : '-'}
                </Descriptions.Item>

                <Descriptions.Item label="Description">
                    {transaction.description || '-'}
                </Descriptions.Item>

                <Descriptions.Item label="Notes">
                    {transaction.notes || '-'}
                </Descriptions.Item>

                <Descriptions.Item label="Date">
                    {dayjs(transaction.transaction_date).format('MMMM D, YYYY')}
                </Descriptions.Item>

                <Descriptions.Item label="Account">
                    {transaction.account?.name || '-'}
                </Descriptions.Item>

                {transaction.type === 'transfer' && (
                    <Descriptions.Item label="To Account">
                        {transaction.to_account?.name || '-'}
                    </Descriptions.Item>
                )}

                <Descriptions.Item label="Category">
                    {transaction.category?.name || '-'}
                </Descriptions.Item>

                <Descriptions.Item label="Reconciled">
                    <Tag color={transaction.is_reconciled ? 'green' : 'default'}>
                        {transaction.is_reconciled ? 'Yes' : 'No'}
                    </Tag>
                </Descriptions.Item>

                <Descriptions.Item label="Source">
                    {transaction.source || 'manual'}
                </Descriptions.Item>
            </Descriptions>

            {/* Tags */}
            {transaction.tags && transaction.tags.length > 0 && (
                <div style={{ marginBottom: 16 }}>
                    <strong>Tags:</strong>
                    <div style={{ marginTop: 4 }}>
                        <Space size={4} wrap>
                            {transaction.tags.map((tag) => (
                                <Tag key={tag.id} color={tag.color || 'default'}>{tag.name}</Tag>
                            ))}
                        </Space>
                    </div>
                </div>
            )}

            {/* Splits */}
            <div>
                <strong>Splits:</strong>
                {transaction.splits && transaction.splits.length > 0 ? (
                    <List
                        size="small"
                        dataSource={transaction.splits}
                        renderItem={(split) => (
                            <List.Item>
                                <List.Item.Meta
                                    title={`${Number(split.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}`}
                                    description={split.description || 'No description'}
                                />
                            </List.Item>
                        )}
                        style={{ marginTop: 8 }}
                    />
                ) : (
                    <Empty description="No splits" image={Empty.PRESENTED_IMAGE_SIMPLE} />
                )}
            </div>

            {/* Receipt */}
            {transaction.receipt_path && (
                <div style={{ marginTop: 16 }}>
                    <strong>Receipt:</strong>
                    <Image
                        src={`/storage/${transaction.receipt_path}`}
                        alt="Receipt"
                        style={{ marginTop: 8, maxWidth: '100%' }}
                        fallback="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII="
                    />
                </div>
            )}
        </Drawer>
    );
}
