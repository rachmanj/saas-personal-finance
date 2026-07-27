import { useEffect, useState, useMemo } from 'react';
import { Timeline, Tag, Typography, Spin, Alert, Empty, App } from 'antd';
import { apiGet } from '../../utils/api';
import dayjs from 'dayjs';

const { Text } = Typography;

const TYPE_COLORS = {
    income: 'green',
    expense: 'red',
    transfer: 'blue',
};

export default function UpcomingPreview() {
    const { message } = App.useApp();
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        setLoading(true);
        apiGet('/api/recurring-transactions/upcoming')
            .then((res) => {
                setItems(res.data || []);
                setError(null);
            })
            .catch((err) => {
                setError(err.message || 'Failed to load upcoming transactions');
                message.error('Failed to load upcoming transactions');
            })
            .finally(() => setLoading(false));
    }, [message]);

    const grouped = useMemo(() => {
        const groups = {};
        items.forEach((item) => {
            const date = dayjs(item.next_due_date).format('YYYY-MM-DD');
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(item);
        });
        return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b));
    }, [items]);

    if (loading) {
        return <Spin />;
    }

    if (error) {
        return <Alert message="Error" description={error} type="error" showIcon />;
    }

    if (items.length === 0) {
        return <Empty description="No upcoming recurring transactions" image={Empty.PRESENTED_IMAGE_SIMPLE} />;
    }

    return (
        <Timeline
            items={grouped.map(([date, dayItems]) => ({
                key: date,
                label: dayjs(date).format('MMM D, YYYY'),
                children: dayItems.map((item) => (
                    <div key={item.id} style={{ marginBottom: 8 }}>
                        <Tag color={TYPE_COLORS[item.type] || 'default'}>{item.type}</Tag>
                        <Text>{item.description || 'No description'}</Text>
                        {' '}
                        <Text type={item.type === 'income' ? 'success' : item.type === 'expense' ? 'danger' : 'secondary'} strong>
                            {item.currency} {Number(item.amount).toFixed(2)}
                        </Text>
                    </div>
                )),
            }))}
        />
    );
}
