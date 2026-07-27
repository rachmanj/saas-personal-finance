import { useEffect, useState } from 'react';
import { Alert, List, Tag, Empty, Spin, App } from 'antd';
import { apiGet } from '../../utils/api';
import BudgetProgressBar from './BudgetProgressBar';

const STATUS_CONFIG = {
    warning: { type: 'warning', color: 'orange', label: 'Warning' },
    over: { type: 'error', color: 'red', label: 'Over Budget' },
};

/**
 * Banner showing budgets near or over their notification threshold.
 */
export default function BudgetAlert() {
    const { message } = App.useApp();
    const [alerts, setAlerts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        setLoading(true);
        apiGet('/api/budgets/alerts')
            .then((res) => {
                setAlerts(res.data || []);
                setError(null);
            })
            .catch((err) => {
                setError(err.message || 'Failed to load budget alerts');
                message.error('Failed to load budget alerts');
            })
            .finally(() => setLoading(false));
    }, [message]);

    if (loading) {
        return <Spin />;
    }

    if (error) {
        return <Alert message="Error" description={error} type="error" showIcon />;
    }

    if (alerts.length === 0) {
        return <Empty description="No budget alerts" image={Empty.PRESENTED_IMAGE_SIMPLE} />;
    }

    return (
        <List
            dataSource={alerts}
            renderItem={(budget) => {
                const status = budget.utilization?.status || 'warning';
                const config = STATUS_CONFIG[status] || STATUS_CONFIG.warning;

                return (
                    <List.Item>
                        <Alert
                            style={{ width: '100%' }}
                            type={config.type}
                            showIcon
                            message={
                                <span>
                                    {budget.category?.name || 'Unknown category'}
                                    {' '}
                                    <Tag color={config.color}>{config.label}</Tag>
                                </span>
                            }
                            description={
                                <BudgetProgressBar
                                    percent={budget.utilization?.percent ?? 0}
                                    status={status}
                                />
                            }
                        />
                    </List.Item>
                );
            }}
        />
    );
}
