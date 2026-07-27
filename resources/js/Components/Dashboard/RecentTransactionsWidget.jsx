import { List, Tag, Typography } from 'antd';
import dayjs from 'dayjs';
import EmptyState from '../Shared/EmptyState';

const { Text } = Typography;

const typeColors = {
    income: 'green',
    expense: 'red',
    transfer: 'blue',
};

export default function RecentTransactionsWidget({ transactions = [] }) {
    if (transactions.length === 0) {
        return <EmptyState description="No recent transactions" />;
    }

    return (
        <List
            dataSource={transactions}
            renderItem={(txn) => (
                <List.Item>
                    <List.Item.Meta
                        title={
                            <span>
                                <Tag color={typeColors[txn.type] || 'default'}>
                                    {txn.type}
                                </Tag>
                                <Text>{txn.description || 'No description'}</Text>
                            </span>
                        }
                        description={
                            <span>
                                {txn.account?.name || 'Unknown account'}
                                {txn.category ? ` · ${txn.category.name}` : ''}
                                {' · '}
                                {dayjs(txn.transaction_date).format('MMM D, YYYY')}
                            </span>
                        }
                    />
                    <Text
                        type={txn.type === 'income' ? 'success' : txn.type === 'expense' ? 'danger' : 'secondary'}
                        strong
                    >
                        {txn.type === 'income' ? '+' : txn.type === 'expense' ? '-' : ''}
                        {Number(txn.base_amount || txn.amount).toFixed(2)} {txn.base_currency || txn.currency}
                    </Text>
                </List.Item>
            )}
        />
    );
}