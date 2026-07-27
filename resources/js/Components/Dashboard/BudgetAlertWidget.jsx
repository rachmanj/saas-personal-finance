import { useEffect, useState } from 'react';
import { List, Spin, Empty, App } from 'antd';
import { apiGet } from '../../utils/api';
import BudgetProgressBar from '../Budgets/BudgetProgressBar';

export default function BudgetAlertWidget() {
    const { message } = App.useApp();
    const [alerts, setAlerts] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true);
        apiGet('/api/budgets/alerts')
            .then((res) => setAlerts(res.data || []))
            .catch(() => message.error('Failed to load budget alerts'))
            .finally(() => setLoading(false));
    }, [message]);

    if (loading) {
        return <Spin />;
    }

    if (alerts.length === 0) {
        return (
            <Empty
                description="No budget alerts"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
            />
        );
    }

    return (
        <List
            dataSource={alerts}
            renderItem={(budget) => (
                <List.Item>
                    <List.Item.Meta
                        title={budget.category?.name || 'Unknown category'}
                        description={
                            <BudgetProgressBar
                                percent={budget.utilization?.percent ?? 0}
                                status={budget.utilization?.status ?? 'ok'}
                            />
                        }
                    />
                </List.Item>
            )}
        />
    );
}
