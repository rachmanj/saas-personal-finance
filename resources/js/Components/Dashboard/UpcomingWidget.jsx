import { useEffect, useState } from 'react';
import { List, Tag, Typography, Spin, Empty, App } from 'antd';
import { apiGet } from '../../utils/api';
import dayjs from 'dayjs';

const { Text } = Typography;

const TYPE_COLORS = {
    income: 'green',
    expense: 'red',
    transfer: 'blue',
};

export default function UpcomingWidget() {
    const { message } = App.useApp();
    const [upcoming, setUpcoming] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true);
        apiGet('/api/recurring-transactions/upcoming')
            .then((res) => setUpcoming(res.data || []))
            .catch(() => message.error('Failed to load upcoming transactions'))
            .finally(() => setLoading(false));
    }, [message]);

    if (loading) {
        return <Spin />;
    }

    if (upcoming.length === 0) {
        return (
            <Empty
                description="No upcoming recurring transactions"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
            />
        );
    }

    return (
        <List
            dataSource={upcoming}
            renderItem={(item) => (
                <List.Item>
                    <List.Item.Meta
                        title={
                            <span>
                                <Tag color={TYPE_COLORS[item.type] || 'default'}>{item.type}</Tag>
                                <Text>{item.description || 'No description'}</Text>
                            </span>
                        }
                        description={dayjs(item.next_due_date).format('MMM D, YYYY')}
                    />
                    <Text
                        type={item.type === 'income' ? 'success' : item.type === 'expense' ? 'danger' : 'secondary'}
                        strong
                    >
                        {item.currency} {Number(item.amount).toFixed(2)}
                    </Text>
                </List.Item>
            )}
        />
    );
}
